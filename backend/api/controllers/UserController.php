<?php
/**
 * User Controller - Handles user registration, login, and profile management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mailer.php';
require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../helpers.php';

class UserController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    private function shouldBypassEmailVerification(): bool {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isLocalHost = strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
        $localBypassDefault = $_ENV['BYPASS_EMAIL_VERIFICATION_LOCAL'] ?? 'true';
        $allowLocalBypass = filter_var($localBypassDefault, FILTER_VALIDATE_BOOLEAN);
        $forceBypass = filter_var($_ENV['BYPASS_EMAIL_VERIFICATION'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

        return $forceBypass || ($isLocalHost && $allowLocalBypass);
    }

    /**
     * Register a new user
     * POST /api/users/register
     */
    public function register() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();

        // Validate required fields
        $required = ['email', 'password', 'first_name', 'last_name'];
        if (!Middleware::validateRequired($data, $required)) {
            sendError('Missing required fields: ' . implode(', ', $required), 400);
        }

        // Validate email format
        if (!isValidEmail($data['email'])) {
            sendError('Invalid email format', 400);
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            sendError('Password must be at least 8 characters long', 400);
        }

        // Check if user already exists
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                sendError('Email already registered', 409);
            }
        } catch (PDOException $e) {
            sendError('Database error: ' . $e->getMessage(), 500);
        }

        // Create user
        try {
            $password_hash = hashPassword($data['password']);
            $otp_code = generateOTP(6);
            $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt = $this->pdo->prepare("
                INSERT INTO users (email, password_hash, first_name, last_name, phone, otp_code, otp_expires_at, email_verified)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");

            $phone = $data['phone'] ?? null;
            $stmt->execute([
                $data['email'],
                $password_hash,
                $data['first_name'],
                $data['last_name'],
                $phone,
                $otp_code,
                $otp_expires
            ]);

            $user_id = $this->pdo->lastInsertId();

            // Send verification email with OTP
            $emailSent = $this->sendVerificationEmail($data['email'], $data['first_name'], $otp_code);
            
            if (!$emailSent) {
                // Email sending failed - log  and inform user
                error_log("CRITICAL: Failed to send verification email to: " . $data['email']);
                // Still allow registration to complete, but warn user
                sendResponse([
                    'success' => true,
                    'message' => 'User registered successfully. However, there was an issue sending the verification email. Please use the resend OTP option.',
                    'user_id' => $user_id,
                    'email' => $data['email'],
                    'email_warning' => true
                ], 201);
                return;
            }

            // Log audit trail
            Middleware::logAuditTrail($user_id, 'USER_CREATED', 'users', $user_id, null, [
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name']
            ]);

            sendResponse([
                'success' => true,
                'message' => 'User registered successfully. Please verify your email using the OTP code sent to your email address.',
                'user_id' => $user_id,
                'email' => $data['email']
            ], 201);

        } catch (PDOException $e) {
            sendError('Failed to register user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify email with OTP
     * POST /api/users/verify-email
     */
    public function verifyEmail() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();

        // Validate required fields
        if (!Middleware::validateRequired($data, ['email', 'otp_code'])) {
            sendError('Email and OTP code required', 400);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, otp_code, otp_expires_at, otp_attempts, email_verified
                FROM users
                WHERE email = ?
            ");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user) {
                sendError('User not found', 404);
            }

            // Check if already verified
            if ($user['email_verified']) {
                sendError('Email already verified', 400);
            }

            // Check OTP attempts
            if ($user['otp_attempts'] >= 5) {
                sendError('Maximum OTP attempts exceeded. Please request a new OTP.', 429);
            }

            // Check OTP expiration
            if (strtotime($user['otp_expires_at']) < time()) {
                sendError('OTP code has expired', 400);
            }

            // Verify OTP
            if ($user['otp_code'] !== $data['otp_code']) {
                // Increment attempts
                $stmt = $this->pdo->prepare("
                    UPDATE users
                    SET otp_attempts = otp_attempts + 1
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);

                sendError('Invalid OTP code', 400);
            }

            // Update user - mark as verified
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET email_verified = 1, email_verified_at = NOW(), otp_code = NULL, otp_expires_at = NULL, otp_attempts = 0
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);

            // Log audit trail
            Middleware::logAuditTrail($user['id'], 'EMAIL_VERIFIED', 'users', $user['id']);

            sendResponse([
                'success' => true,
                'message' => 'Email verified successfully',
                'user_id' => $user['id']
            ], 200);
        } catch (PDOException $e) {
            sendError('Verification failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Resend OTP code
     * POST /api/users/resend-otp
     */
    public function resendOTP() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();

        if (!isset($data['email'])) {
            sendError('Email is required', 400);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, first_name, email_verified
                FROM users
                WHERE email = ?
            ");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user) {
                sendError('User not found', 404);
            }

            if ($user['email_verified']) {
                sendError('Email is already verified', 400);
            }

            // Generate new OTP
            $otp_code = generateOTP(6);
            $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt = $this->pdo->prepare("
                UPDATE users
                SET otp_code = ?, otp_expires_at = ?, otp_attempts = 0
                WHERE id = ?
            ");
            $stmt->execute([$otp_code, $otp_expires, $user['id']]);

            // Send verification email
            $emailSent = $this->sendVerificationEmail($data['email'], $user['first_name'], $otp_code);
            
            if (!$emailSent) {
                error_log("CRITICAL: Failed to resend OTP email to: " . $data['email']);
                sendError('Failed to send OTP email. Please try again later.', 500);
            }

            // Log audit trail
            Middleware::logAuditTrail($user['id'], 'OTP_RESENT', 'users', $user['id']);

            sendResponse([
                'success' => true,
                'message' => 'OTP code sent to your email address'
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to resend OTP: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Login user
     * POST /api/users/login
     */
    public function login() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();

        // Validate required fields
        if (!Middleware::validateRequired($data, ['email', 'password'])) {
            sendError('Email and password required', 400);
        }

        // Rate limiting
        if (!Middleware::rateLimit($data['email'], 5, 300)) {
            sendError('Too many login attempts. Please try again later.', 429);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email, password_hash, first_name, last_name, email_verified, is_active, role
                FROM users
                WHERE email = ?
            ");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();
            
            // Update last_login
            if ($user) {
                $updateLogin = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateLogin->execute([$user['id']]);
            }

            if (!$user) {
                sendError('Invalid email or password', 401);
            }

            if (!$user['is_active']) {
                sendError('Account is inactive', 403);
            }

            if (!$user['email_verified'] && !$this->shouldBypassEmailVerification()) {
                sendError('Please verify your email address first', 403);
            }

            if (!$user['email_verified'] && $this->shouldBypassEmailVerification()) {
                error_log('Email verification bypassed for local/dev login: ' . $user['email']);
            }

            // Verify password
            if (!verifyPassword($data['password'], $user['password_hash'])) {
                sendError('Invalid email or password', 401);
            }

            // Generate token
            $token = bin2hex(random_bytes(32));

            // Store session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['token'] = $token;

            // Log audit trail
            Middleware::logAuditTrail($user['id'], 'USER_LOGIN', 'users', $user['id']);

            sendResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'role' => $user['role']
                ],
                'token' => $token
            ], 200);
        } catch (PDOException $e) {
            sendError('Login failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Logout user
     * POST /api/users/logout
     */
    public function logout() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        // Log audit trail
        Middleware::logAuditTrail($user['user_id'], 'USER_LOGOUT', 'users', $user['user_id']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();

        sendResponse([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Get user profile
     * GET /api/users/{id}
     */
    public function getProfile($user_id) {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email, first_name, last_name, phone, location, is_active, created_at
                FROM users
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user) {
                sendError('User not found', 404);
            }

            sendResponse([
                'success' => true,
                'user' => $user
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to fetch user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update user profile
     * PUT /api/users/{id}
     */
    public function updateProfile($user_id) {
        if (!Middleware::validateMethod('PUT')) {
            sendError('Method not allowed', 405);
        }

        $auth_user = Middleware::requireAuth();

        // Check if user owns this profile
        if (!Middleware::ownsResource($auth_user['user_id'], $user_id)) {
            sendError('Unauthorized: Cannot update other user profiles', 403);
        }

        $data = getRequestData();

        // Allowed fields for update
        $allowed_fields = ['first_name', 'last_name', 'phone', 'location'];
        $update_fields = [];
        $update_values = [];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_fields[] = "$field = ?";
                $update_values[] = $data[$field];
            }
        }

        if (empty($update_fields)) {
            sendError('No fields to update', 400);
        }

        $update_values[] = $user_id;

        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET " . implode(', ', $update_fields) . "
                WHERE id = ?
            ");
            $stmt->execute($update_values);

            // Fetch the updated user data
            $stmt = $this->pdo->prepare("
                SELECT id, email, first_name, last_name, phone, location, role, created_at
                FROM users
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            $updated_user = $stmt->fetch();

            // Log audit trail
            Middleware::logAuditTrail($user_id, 'USER_UPDATED', 'users', $user_id, null, $data);

            sendResponse([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $updated_user
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send verification email with OTP
     */
    private function sendVerificationEmail($email, $first_name, $otp_code) {
        $mail = null;
        try {
            $mail = mailer();
            $mail->addAddress($email, $first_name);
            $mail->Subject = 'Email Verification - Civic Connect';
            $mail->Body = $this->getVerificationEmailHTML($first_name, $otp_code);

            $result = $mail->send();
            
            if ($result) {
                error_log("Email sent successfully to: {$email} with OTP: {$otp_code}");
            } else {
                error_log("Email send returned false for: {$email}");
            }
            
            return $result;
        } catch (Exception $e) {
            // Log detailed error information
            error_log('Email sending failed: ' . $e->getMessage());
            if ($mail !== null) {
                error_log('Mail Error Info: ' . $mail->ErrorInfo);
            }
            error_log('Recipient: ' . $email);
            return false;
        }
    }

    /**
     * Get verification email HTML template
     */
    private function getVerificationEmailHTML($first_name, $otp_code) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; background-color: #f4f4f4;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px;'>
                    <h2>Welcome to Civic Connect, {$first_name}!</h2>
                    <p>Thank you for registering. Please verify your email address using the OTP code below:</p>
                    <div style='background-color: #f0f0f0; padding: 20px; border-radius: 4px; text-align: center; margin: 20px 0;'>
                        <h3 style='font-size: 24px; letter-spacing: 2px; margin: 0;'>{$otp_code}</h3>
                    </div>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you didn't create this account, please ignore this email.</p>
                    <hr>
                    <p style='color: #777; font-size: 12px;'>Civic Connect - Building Better Communities</p>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Send password reset code
     * POST /api/users/forgot-password
     */
    public function sendPasswordResetCode() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();

        if (!isset($data['email'])) {
            sendError('Email is required', 400);
        }

        // Validate email format
        if (!isValidEmail($data['email'])) {
            sendError('Invalid email format', 400);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, first_name, email_verified, is_active
                FROM users
                WHERE email = ?
            ");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user) {
                // For security, don't reveal if email exists
                sendResponse([
                    'success' => true,
                    'message' => 'If this email is registered, a reset code has been sent'
                ], 200);
                return;
            }

            if (!$user['is_active']) {
                sendError('Account is inactive', 403);
            }

            if (!$user['email_verified']) {
                sendError('Please verify your email address first', 403);
            }

            // Generate reset code
            $reset_code = generateOTP(6);
            $reset_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt = $this->pdo->prepare("
                UPDATE users
                SET otp_code = ?, otp_expires_at = ?, otp_attempts = 0
                WHERE id = ?
            ");
            $stmt->execute([$reset_code, $reset_expires, $user['id']]);

            // Send password reset email
            $emailSent = $this->sendPasswordResetEmail($data['email'], $user['first_name'], $reset_code);
            
            if (!$emailSent) {
                error_log("CRITICAL: Failed to send password reset email to: " . $data['email']);
                sendError('Failed to send reset code. Please try again later.', 500);
            }

            // Log audit trail
            Middleware::logAuditTrail($user['id'], 'PASSWORD_RESET_REQUESTED', 'users', $user['id']);

            sendResponse([
                'success' => true,
                'message' => 'Password reset code sent to your email address'
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to process request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify password reset code
     * POST /api/users/verify-reset-code
     */
    public function verifyPasswordResetCode() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();

        // Validate required fields
        if (!Middleware::validateRequired($data, ['email', 'reset_code'])) {
            sendError('Email and reset code required', 400);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, otp_code, otp_expires_at, otp_attempts, email_verified, is_active
                FROM users
                WHERE email = ?
            ");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user) {
                sendError('Invalid email or reset code', 400);
            }

            if (!$user['is_active']) {
                sendError('Account is inactive', 403);
            }

            if (!$user['email_verified']) {
                sendError('Please verify your email address first', 403);
            }

            // Check OTP attempts
            if ($user['otp_attempts'] >= 5) {
                sendError('Maximum attempts exceeded. Please request a new reset code.', 429);
            }

            // Check OTP expiration
            if (strtotime($user['otp_expires_at']) < time()) {
                sendError('Reset code has expired', 400);
            }

            // Verify OTP
            if ($user['otp_code'] !== $data['reset_code']) {
                // Increment attempts
                $stmt = $this->pdo->prepare("
                    UPDATE users
                    SET otp_attempts = otp_attempts + 1
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);

                sendError('Invalid reset code', 400);
            }

            sendResponse([
                'success' => true,
                'message' => 'Reset code verified successfully'
            ], 200);
        } catch (PDOException $e) {
            sendError('Verification failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reset password with verified code
     * POST /api/users/reset-password
     */
    public function resetPassword() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();

        // Validate required fields
        if (!Middleware::validateRequired($data, ['email', 'reset_code', 'new_password'])) {
            sendError('Email, reset code, and new password required', 400);
        }

        // Validate password strength
        if (strlen($data['new_password']) < 8) {
            sendError('Password must be at least 8 characters long', 400);
        }

        if (!preg_match('/(?=.*[a-z])/', $data['new_password'])) {
            sendError('Password must contain at least one lowercase letter', 400);
        }

        if (!preg_match('/(?=.*[A-Z])/', $data['new_password'])) {
            sendError('Password must contain at least one uppercase letter', 400);
        }

        if (!preg_match('/(?=.*\d)/', $data['new_password'])) {
            sendError('Password must contain at least one number', 400);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, first_name, otp_code, otp_expires_at, otp_attempts, email_verified, is_active
                FROM users
                WHERE email = ?
            ");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user) {
                sendError('Invalid email or reset code', 400);
            }

            if (!$user['is_active']) {
                sendError('Account is inactive', 403);
            }

            if (!$user['email_verified']) {
                sendError('Please verify your email address first', 403);
            }

            // Check OTP attempts
            if ($user['otp_attempts'] >= 5) {
                sendError('Maximum attempts exceeded. Please request a new reset code.', 429);
            }

            // Check OTP expiration
            if (strtotime($user['otp_expires_at']) < time()) {
                sendError('Reset code has expired', 400);
            }

            // Verify reset code one more time
            if ($user['otp_code'] !== $data['reset_code']) {
                // Increment attempts
                $stmt = $this->pdo->prepare("
                    UPDATE users
                    SET otp_attempts = otp_attempts + 1
                    WHERE id = ?
                ");
                $stmt->execute([$user['id']]);

                sendError('Invalid reset code', 400);
            }

            // Update password and clear reset code
            $password_hash = hashPassword($data['new_password']);
            
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET password_hash = ?, otp_code = NULL, otp_expires_at = NULL, otp_attempts = 0
                WHERE id = ?
            ");
            $stmt->execute([$password_hash, $user['id']]);

            // Send confirmation email
            $this->sendPasswordChangedEmail($data['email'], $user['first_name']);

            // Log audit trail
            Middleware::logAuditTrail($user['id'], 'PASSWORD_RESET_COMPLETED', 'users', $user['id']);

            sendResponse([
                'success' => true,
                'message' => 'Password reset successfully'
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to reset password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send password reset email with code
     */
    private function sendPasswordResetEmail($email, $first_name, $reset_code) {
        try {
            $mail = mailer();
            $mail->addAddress($email, $first_name);
            $mail->Subject = 'Password Reset - Civic Connect';
            $mail->Body = $this->getPasswordResetEmailHTML($first_name, $reset_code);

            $result = $mail->send();
            
            if ($result) {
                error_log("Password reset email sent successfully to: {$email}");
            } else {
                error_log("Password reset email send returned false for: {$email}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Password reset email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get password reset email HTML template
     */
    private function getPasswordResetEmailHTML($first_name, $reset_code) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; background-color: #f4f4f4;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px;'>
                    <h2>Password Reset Request</h2>
                    <p>Hi {$first_name},</p>
                    <p>We received a request to reset your password for your Civic Connect account. Please use the code below to reset your password:</p>
                    <div style='background-color: #f0f0f0; padding: 20px; border-radius: 4px; text-align: center; margin: 20px 0;'>
                        <h3 style='font-size: 24px; letter-spacing: 2px; margin: 0;'>{$reset_code}</h3>
                    </div>
                    <p>This code will expire in 10 minutes.</p>
                    <p><strong>If you didn't request this password reset, please ignore this email and your password will remain unchanged.</strong></p>
                    <hr>
                    <p style='color: #777; font-size: 12px;'>Civic Connect - Building Better Communities</p>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Send password changed confirmation email
     */
    private function sendPasswordChangedEmail($email, $first_name) {
        try {
            $mail = mailer();
            $mail->addAddress($email, $first_name);
            $mail->Subject = 'Password Changed Successfully - Civic Connect';
            $mail->Body = $this->getPasswordChangedEmailHTML($first_name);

            $result = $mail->send();
            
            if ($result) {
                error_log("Password changed confirmation sent to: {$email}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Password changed email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get password changed email HTML template
     */
    private function getPasswordChangedEmailHTML($first_name) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; background-color: #f4f4f4;'>
                <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px;'>
                    <h2>Password Changed Successfully</h2>
                    <p>Hi {$first_name},</p>
                    <p>Your password has been successfully changed. You can now log in with your new password.</p>
                    <p><strong>If you didn't make this change, please contact us immediately.</strong></p>
                    <hr>
                    <p style='color: #777; font-size: 12px;'>Civic Connect - Building Better Communities</p>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Get user statistics
     * GET /api/users/{id}/stats
     */
    public function getUserStats($user_id) {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        $auth_user = Middleware::requireAuth();

        // Check if user can access these stats (own profile or admin/staff)
        // Removed restriction to allow viewing other users' stats
        // if (!Middleware::ownsResource($auth_user['user_id'], $user_id) && !in_array($auth_user['role'], ['admin', 'staff'])) {
        //    sendError('Unauthorized: Cannot view other user stats', 403);
        // }

        try {
            // Get total issues reported by the user
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total_issues
                FROM issues
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $issues_result = $stmt->fetch();
            $total_issues = $issues_result['total_issues'] ?? 0;

            // Get total upvotes received on user's issues
            $stmt = $this->pdo->prepare("
                SELECT COUNT(u.id) as total_upvotes
                FROM upvotes u
                JOIN issues i ON u.issue_id = i.id
                WHERE i.user_id = ?
            ");
            $stmt->execute([$user_id]);
            $upvotes_result = $stmt->fetch();
            $total_upvotes = $upvotes_result['total_upvotes'] ?? 0;

            sendResponse([
                'success' => true,
                'stats' => [
                    'issues_reported' => (int)$total_issues,
                    'upvotes_received' => (int)$total_upvotes
                ]
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to fetch user stats: ' . $e->getMessage(), 500);
        }
    }
}
?>
