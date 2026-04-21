<?php
/**
 * File Controller - Handles file upload and deletion
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../helpers.php';

class FileController {
    private $pdo;
    private $upload_dirs = [
        'issues' => '../uploads/issues/'
        // 'profiles' => '../uploads/profiles/' // Removed: profile images no longer supported
    ];

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Upload image for issue
     * POST /api/upload/issue
     */
    public function uploadIssueImage() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        // Validate file upload
        if (!isset($_FILES['image'])) {
            sendError('No file uploaded', 400);
        }

        $upload_result = uploadIssueImage($_FILES['image'], __DIR__ . '/' . $this->upload_dirs['issues']);

        if (!$upload_result['success']) {
            sendError($upload_result['error'], 400);
        }

        // Log audit trail
        Middleware::logAuditTrail($user['user_id'], 'FILE_UPLOADED', 'files', null, null, [
            'type' => 'issue_image',
            'path' => $upload_result['path']
        ]);

        sendResponse([
            'success' => true,
            'message' => 'File uploaded successfully',
            'file' => [
                'path' => $upload_result['path'],
                'filename' => $upload_result['filename']
            ]
        ], 201);
    }

    /**
     * Upload profile image
     * POST /api/upload/profile
     * DEPRECATED: Profile images are no longer supported
     */
    /*
    public function uploadProfileImage() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        // Validate file upload
        if (!isset($_FILES['image'])) {
            sendError('No file uploaded', 400);
        }

        $upload_result = uploadIssueImage(
            $_FILES['image'],
            __DIR__ . '/' . $this->upload_dirs['profiles']
        );

        if (!$upload_result['success']) {
            sendError($upload_result['error'], 400);
        }

        try {
            // Update user profile image in database
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET profile_image = ?
                WHERE id = ?
            ");
            $stmt->execute([$upload_result['path'], $user['user_id']]);

            // Log audit trail
            Middleware::logAuditTrail($user['user_id'], 'PROFILE_IMAGE_UPDATED', 'users', $user['user_id'], null, [
                'path' => $upload_result['path']
            ]);

            sendResponse([
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'file' => [
                    'path' => $upload_result['path'],
                    'filename' => $upload_result['filename']
                ]
            ], 201);
        } catch (PDOException $e) {
            // Delete uploaded file if database update fails
            deleteImage($upload_result['path']);
            sendError('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }
    */

    /**
     * Delete a file
     * DELETE /api/files
     */
    public function deleteFile() {
        if (!Middleware::validateMethod('DELETE')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();
        $data = getRequestData();

        if (!isset($data['filepath'])) {
            sendError('File path is required', 400);
        }

        $filepath = $data['filepath'];

        // Validate filepath to prevent directory traversal
        if (strpos($filepath, '..') !== false || strpos($filepath, '//') !== false) {
            sendError('Invalid file path', 400);
        }

        try {
            // If deleting issue image, verify ownership
            if (strpos($filepath, 'issues') !== false) {
                // Extract issue_id from filepath or get it from query
                $issue_id = $data['issue_id'] ?? null;
                if ($issue_id) {
                    $stmt = $this->pdo->prepare("SELECT user_id FROM issues WHERE id = ?");
                    $stmt->execute([$issue_id]);
                    $issue = $stmt->fetch();

                    if (!$issue) {
                        sendError('Issue not found', 404);
                    }

                    if (!Middleware::ownsResource($user['user_id'], $issue['user_id'])) {
                        sendError('Unauthorized: Cannot delete other user\'s files', 403);
                    }
                }
            }

            // Delete file
            if (!deleteImage($filepath)) {
                sendError('Failed to delete file', 500);
            }

            // Log audit trail
            Middleware::logAuditTrail($user['user_id'], 'FILE_DELETED', 'files', null, null, [
                'path' => $filepath
            ]);

            sendResponse([
                'success' => true,
                'message' => 'File deleted successfully'
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to process request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update issue image
     * PUT /api/issues/{id}/image
     */
    public function updateIssueImage($issue_id) {
        if (!Middleware::validateMethod('PUT')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        if (!isset($_FILES['image'])) {
            sendError('No file uploaded', 400);
        }

        try {
            // Get current issue
            $stmt = $this->pdo->prepare("SELECT user_id, image_path FROM issues WHERE id = ?");
            $stmt->execute([$issue_id]);
            $issue = $stmt->fetch();

            if (!$issue) {
                sendError('Issue not found', 404);
            }

            // Check ownership
            if (!Middleware::ownsResource($user['user_id'], $issue['user_id'])) {
                sendError('Unauthorized: Cannot update other user\'s issues', 403);
            }

            // Upload new image
            $upload_result = uploadIssueImage(
                $_FILES['image'],
                __DIR__ . '/' . $this->upload_dirs['issues']
            );

            if (!$upload_result['success']) {
                sendError($upload_result['error'], 400);
            }

            // Delete old image
            if ($issue['image_path']) {
                deleteImage($issue['image_path']);
            }

            // Update issue with new image path
            $stmt = $this->pdo->prepare("
                UPDATE issues
                SET image_path = ?
                WHERE id = ?
            ");
            $stmt->execute([$upload_result['path'], $issue_id]);

            // Log audit trail
            Middleware::logAuditTrail($user['user_id'], 'ISSUE_IMAGE_UPDATED', 'issues', $issue_id, null, [
                'path' => $upload_result['path']
            ]);

            sendResponse([
                'success' => true,
                'message' => 'Issue image updated successfully',
                'file' => [
                    'path' => $upload_result['path'],
                    'filename' => $upload_result['filename']
                ]
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to update issue image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get file info
     * GET /api/files/{filename}
     */
    public function getFileInfo($filename) {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        // Validate filename
        if (empty($filename) || preg_match('/\.\./', $filename)) {
            sendError('Invalid filename', 400);
        }

        try {
            $full_path = __DIR__ . '/../uploads/' . $filename;

            if (!file_exists($full_path)) {
                sendError('File not found', 404);
            }

            sendResponse([
                'success' => true,
                'file' => [
                    'name' => basename($full_path),
                    'size' => filesize($full_path),
                    'type' => mime_content_type($full_path),
                    'created' => filemtime($full_path)
                ]
            ], 200);
        } catch (Exception $e) {
            sendError('Failed to get file info: ' . $e->getMessage(), 500);
        }
    }
}
?>
