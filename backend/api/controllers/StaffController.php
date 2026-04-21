<?php
/**
 * Staff Controller - Handles staff account management for admins
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mailer.php';
require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../helpers.php';

class StaffController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Middleware check for admin access
     */
    private function requireAdmin() {
        $user = Middleware::requireAuth();
        
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user['user_id']]);
        $dbUser = $stmt->fetch();

        if (!$dbUser || $dbUser['role'] !== 'admin') {
            sendError('Unauthorized: Admin access required', 403);
        }

        return $user;
    }

    /**
     * Get all staff members
     * GET /api/admin/staff
     */
    public function getAllStaff() {
        $this->requireAdmin();

        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        try {
            $query = "SELECT id, email, first_name, last_name, role, is_active, created_at, last_login, email_verified FROM users WHERE role = 'staff'";
            $params = [];

            if (!empty($search)) {
                $query .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }

            $query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $staff = $stmt->fetchAll();

            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) FROM users WHERE role = 'staff'";
            if (!empty($search)) {
                $countQuery .= " AND (email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
            }
            $countStmt = $this->pdo->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            sendResponse([
                'success' => true,
                'staff' => $staff,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'limit' => $limit
                ]
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to fetch staff: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new staff member
     * POST /api/admin/staff
     */
    public function createStaff() {
        $admin = $this->requireAdmin();

        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();

        // Validate required fields
        if (!isset($data['email']) || !isset($data['first_name']) || !isset($data['last_name']) || !isset($data['password'])) {
            sendError('Missing required fields: email, first_name, last_name, password', 400);
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            sendError('Invalid email format', 400);
        }

        // Validate password strength (minimum 8 characters)
        if (strlen($data['password']) < 8) {
            sendError('Password must be at least 8 characters long', 400);
        }

        try {
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                sendError('Email already exists', 409);
            }

            // Hash password
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert new staff member - auto-verify since created by admin
            $stmt = $this->pdo->prepare("
                INSERT INTO users (email, password_hash, first_name, last_name, role, email_verified, is_active) 
                VALUES (?, ?, ?, ?, 'staff', 1, 1)
            ");
            $stmt->execute([
                $data['email'],
                $passwordHash,
                $data['first_name'],
                $data['last_name']
            ]);

            $staffId = $this->pdo->lastInsertId();

            // Send welcome email
            $this->sendWelcomeEmail($data['email'], $data['first_name']);

            // Log action
            Middleware::logAuditTrail($admin['user_id'], 'STAFF_CREATE', 'users', $staffId, null, [
                'email' => $data['email'],
                'name' => $data['first_name'] . ' ' . $data['last_name']
            ]);

            sendResponse([
                'success' => true,
                'message' => 'Staff account created successfully',
                'staff_id' => $staffId
            ], 201);

        } catch (PDOException $e) {
            sendError('Failed to create staff account: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single staff member details
     * GET /api/admin/staff/{id}
     */
    public function getStaff($staffId) {
        $this->requireAdmin();

        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, email, first_name, last_name, phone, location, role, is_active, 
                       email_verified, created_at, last_login, updated_at 
                FROM users 
                WHERE id = ? AND role = 'staff'
            ");
            $stmt->execute([$staffId]);
            $staff = $stmt->fetch();

            if (!$staff) {
                sendError('Staff member not found', 404);
            }

            sendResponse([
                'success' => true,
                'staff' => $staff
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to fetch staff details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update staff member details
     * PUT /api/admin/staff/{id}
     */
    public function updateStaff($staffId) {
        $admin = $this->requireAdmin();

        if (!Middleware::validateMethod('PUT')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();

        try {
            // Verify staff exists
            $stmt = $this->pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ? AND role = 'staff'");
            $stmt->execute([$staffId]);
            $staff = $stmt->fetch();

            if (!$staff) {
                sendError('Staff member not found', 404);
            }

            // Build update query dynamically based on provided fields
            $updateFields = [];
            $params = [];

            $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'location'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    // Special validation for email
                    if ($field === 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        sendError('Invalid email format', 400);
                    }

                    // Check if email is changing and if new email already exists
                    if ($field === 'email' && $data[$field] !== $staff['email']) {
                        $checkStmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $checkStmt->execute([$data[$field], $staffId]);
                        if ($checkStmt->fetch()) {
                            sendError('Email already exists', 409);
                        }
                    }

                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }

            if (empty($updateFields)) {
                sendError('No valid fields to update', 400);
            }

            $params[] = $staffId;
            $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            // Log action
            Middleware::logAuditTrail($admin['user_id'], 'STAFF_UPDATE', 'users', $staffId, null, $data);

            sendResponse([
                'success' => true,
                'message' => 'Staff account updated successfully'
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to update staff account: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Toggle staff active status
     * PUT /api/admin/staff/{id}/status
     */
    public function toggleStaffStatus($staffId) {
        $admin = $this->requireAdmin();

        if (!Middleware::validateMethod('PUT')) {
            sendError('Method not allowed', 405);
        }

        try {
            // Get current status
            $stmt = $this->pdo->prepare("SELECT is_active FROM users WHERE id = ? AND role = 'staff'");
            $stmt->execute([$staffId]);
            $staff = $stmt->fetch();

            if (!$staff) {
                sendError('Staff member not found', 404);
            }

            // Toggle status
            $newStatus = $staff['is_active'] ? 0 : 1;

            $stmt = $this->pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $staffId]);

            // Log action
            Middleware::logAuditTrail($admin['user_id'], 'STAFF_STATUS_TOGGLE', 'users', $staffId, 
                ['is_active' => $staff['is_active']], 
                ['is_active' => $newStatus]
            );

            sendResponse([
                'success' => true,
                'message' => 'Staff status updated successfully',
                'is_active' => (bool)$newStatus
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to update staff status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send welcome email to new staff member
     */
    private function sendWelcomeEmail($email, $firstName) {
        try {
            $mailer = mailer();
            $mailer->addAddress($email, $firstName);
            $mailer->Subject = 'Welcome to Civic Connect - Staff Account Created';
            $mailer->Body = $this->getWelcomeEmailHTML($firstName);
            
            $mailer->send();
        } catch (Exception $e) {
            // Log but don't fail the request - staff account is already created
            error_log("Failed to send welcome email to {$email}: " . $e->getMessage());
        }
    }

    /**
     * Get welcome email HTML template
     */
    private function getWelcomeEmailHTML($firstName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;'>
            <table role='presentation' style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td align='center' style='padding: 40px 0;'>
                        <table role='presentation' style='width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                            <tr>
                                <td style='padding: 40px 30px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px 8px 0 0;'>
                                    <h1 style='margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;'>Welcome to Civic Connect</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    <p style='margin: 0 0 20px; font-size: 16px; line-height: 24px; color: #333333;'>
                                        Hi <strong>$firstName</strong>,
                                    </p>
                                    <p style='margin: 0 0 20px; font-size: 16px; line-height: 24px; color: #333333;'>
                                        A staff account has been created for you at Civic Connect. You can now access the staff dashboard to manage civic issues and reports.
                                    </p>
                                    <p style='margin: 0 0 20px; font-size: 16px; line-height: 24px; color: #333333;'>
                                        Your account is ready to use! You can log in immediately using the credentials provided to you by your administrator.
                                    </p>
                                    <div style='text-align: center; margin: 30px 0;'>
                                        <a href='http://localhost/civic-connect' style='display: inline-block; background-color: #667eea; color: #ffffff; font-size: 16px; font-weight: bold; padding: 15px 30px; border-radius: 8px; text-decoration: none;'>Login to Your Account</a>
                                    </div>
                                    <p style='margin: 30px 0 0; font-size: 14px; line-height: 20px; color: #666666; text-align: center;'>
                                        If you have any questions, please contact your administrator.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 20px 30px; background-color: #f8f9fa; border-radius: 0 0 8px 8px; text-align: center;'>
                                    <p style='margin: 0; font-size: 14px; color: #666666;'>
                                        © 2024 Civic Connect. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }
}
