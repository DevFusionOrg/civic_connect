<?php
/**
 * Helper functions for file upload and image handling
 */

require_once __DIR__ . '/../config/bootstrap.php';



/**
 * Handle file upload for issues
 * 
 * @param array $file $_FILES array element
 * @param string $upload_dir Upload directory path
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function uploadIssueImage($file, $upload_dir = null) {
    if (!$upload_dir) {
        $upload_dir = __DIR__ . '/../uploads/issues/';
    }

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Validate file upload
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }

    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit'];
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type. Only images allowed'];
    }

    // Generate unique filename
    $filename = uniqid('issue_', true) . '.' . getFileExtension($file['name']);
    $filepath = $upload_dir . $filename;

    // Move uploaded file to destination
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'path' => 'uploads/issues/' . $filename,
            'filename' => $filename
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
}

/**
 * Run pothole detection on an uploaded issue image.
 *
 * @param string $relative_image_path Path relative to backend root (e.g. uploads/issues/file.jpg)
 * @param float $threshold Confidence threshold to treat as pothole detection
 * @param bool $save_annotated_image Whether to save an annotated output image with bounding boxes
 * @return array ['checked'=>bool, 'is_pothole'=>bool, 'confidence'=>float, 'detections'=>array, 'annotated_image_path'=>string|null, 'error'=>string|null]
 */
function detectPotholeFromImage($relative_image_path, $threshold = 0.5, $save_annotated_image = false) {
    $script_path = realpath(__DIR__ . '/../ai/detect.py');
    $image_path = realpath(__DIR__ . '/../' . ltrim($relative_image_path, '/'));

    if (!$script_path || !file_exists($script_path)) {
        return [
            'checked' => false,
            'is_pothole' => false,
            'confidence' => 0,
            'detections' => [],
            'error' => 'AI script not found'
        ];
    }

    if (!$image_path || !file_exists($image_path)) {
        return [
            'checked' => false,
            'is_pothole' => false,
            'confidence' => 0,
            'detections' => [],
            'error' => 'Image file not found for AI detection'
        ];
    }

    $python_bin = getenv('AI_PYTHON_BIN') ?: (stripos(PHP_OS, 'WIN') === 0 ? 'python' : 'python3');

    $annotated_relative_path = null;
    $annotated_absolute_path = null;
    if ($save_annotated_image) {
        $annotated_dir_relative = 'uploads/ai_preview/';
        $annotated_dir_absolute = __DIR__ . '/../' . $annotated_dir_relative;
        if (!is_dir($annotated_dir_absolute)) {
            mkdir($annotated_dir_absolute, 0755, true);
        }
        $annotated_filename = uniqid('ai_', true) . '.jpg';
        $annotated_relative_path = $annotated_dir_relative . $annotated_filename;
        $annotated_absolute_path = realpath($annotated_dir_absolute) . DIRECTORY_SEPARATOR . $annotated_filename;
    }

    $command = escapeshellarg($python_bin) . ' ' . escapeshellarg($script_path) . ' ' . escapeshellarg($image_path);
    if ($annotated_absolute_path) {
        $command .= ' ' . escapeshellarg($annotated_absolute_path);
    }
    $command .= ' 2>&1';
    $output = shell_exec($command);

    if ($output === null) {
        return [
            'checked' => false,
            'is_pothole' => false,
            'confidence' => 0,
            'detections' => [],
            'annotated_image_path' => null,
            'error' => 'Failed to execute AI detection command'
        ];
    }

    $decoded = json_decode(trim($output), true);
    if (!is_array($decoded)) {
        return [
            'checked' => false,
            'is_pothole' => false,
            'confidence' => 0,
            'detections' => [],
            'annotated_image_path' => null,
            'error' => 'Invalid AI response: ' . trim($output)
        ];
    }

    $detections = isset($decoded['detections']) && is_array($decoded['detections']) ? $decoded['detections'] : $decoded;
    $max_confidence = 0;
    $is_pothole = false;

    foreach ($detections as $det) {
        $confidence = isset($det['confidence']) ? (float)$det['confidence'] : 0;
        if ($confidence > $max_confidence) {
            $max_confidence = $confidence;
        }
        if ($confidence > $threshold) {
            $is_pothole = true;
        }
    }

    if ($annotated_relative_path && !file_exists(__DIR__ . '/../' . $annotated_relative_path)) {
        $annotated_relative_path = null;
    }

    return [
        'checked' => true,
        'is_pothole' => $is_pothole,
        'confidence' => $max_confidence,
        'detections' => $detections,
        'annotated_image_path' => $annotated_relative_path,
        'error' => isset($decoded['success']) && !$decoded['success'] ? ($decoded['error'] ?? null) : null
    ];
}

/**
 * Delete an uploaded image
 * 
 * @param string $filepath Relative path to file
 * @return bool
 */
function deleteImage($filepath) {
    $full_path = __DIR__ . '/../' . $filepath;
    if (file_exists($full_path) && is_file($full_path)) {
        return unlink($full_path);
    }
    return false;
}

/**
 * Get file extension
 * 
 * @param string $filename
 * @return string
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Validate email format
 * 
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password using bcrypt
 * 
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 * 
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random OTP code
 * 
 * @param int $length
 * @return string
 */
function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Get request data (handles JSON and form data)
 * 
 * @return array
 */
function getRequestData() {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
    
    return $_POST;
}

/**
 * Send JSON response
 * 
 * @param mixed $data
 * @param int $status_code
 */
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

/**
 * Send error response
 * 
 * @param string $message
 * @param int $status_code
 */
function sendError($message, $status_code = 400) {
    sendResponse(['error' => $message], $status_code);
}

/**
 * Send status change email notification
 * 
 * @param string $to_email Recipient email
 * @param string $to_name Recipient name
 * @param array $issue_data Issue details
 * @param string $old_status Previous status
 * @param string $new_status New status
 * @return bool Success status
 */
function sendStatusChangeEmail($to_email, $to_name, $issue_data, $old_status, $new_status) {
    require_once __DIR__ . '/../config/mailer.php';
    
    try {
        $mail = mailer();
        $mail->addAddress($to_email, $to_name);
        $mail->Subject = "Issue Status Updated: {$issue_data['title']}";
        
        $status_labels = [
            'pending_review' => 'Pending Review',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved'
        ];
        
        $old_label = $status_labels[$old_status] ?? $old_status;
        $new_label = $status_labels[$new_status] ?? $new_status;
        
        $mail->Body = "
            <h2>Your Issue Status Has Been Updated</h2>
            <p>Hello {$to_name},</p>
            <p>The status of your reported issue has been updated:</p>
            
            <div style='background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3 style='margin-top: 0;'>{$issue_data['title']}</h3>
                <p><strong>Issue ID:</strong> #{$issue_data['id']}</p>
                <p><strong>Category:</strong> {$issue_data['category']}</p>
                <p><strong>Status Change:</strong> <span style='color: #f59e0b;'>{$old_label}</span> → <span style='color: #10b981;'>{$new_label}</span></p>
            </div>
            
            <p>You can view the full details of your issue by logging into your account.</p>
            <p style='margin-top: 30px;'>
                <a href='http://localhost:5173/issues/{$issue_data['id']}' 
                   style='background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                    View Issue Details
                </a>
            </p>
            
            <p style='color: #6b7280; font-size: 14px; margin-top: 30px;'>
                Thank you for using Civic Connect to make your community better.
            </p>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email notification failed: " . $e->getMessage());
        return false;
    }
}
?>
