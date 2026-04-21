<?php
/**
 * Notification Controller - Handles user notifications
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../helpers.php';

class NotificationController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get notifications for authenticated user
     * GET /api/notifications
     */
    public function getNotifications() {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, (int)$_GET['limit']) : 20;
        $offset = ($page - 1) * $limit;
        $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

        try {
            $where = 'n.user_id = ?';
            $params = [$user['user_id']];
            
            if ($unread_only) {
                $where .= ' AND n.is_read = 0';
            }

            // Get total count
            $count_stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM notifications n WHERE $where");
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];

            // Get notifications
            $stmt = $this->pdo->prepare("
                SELECT n.*, i.title as issue_title
                FROM notifications n
                LEFT JOIN issues i ON n.issue_id = i.id
                WHERE $where
                ORDER BY n.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $param_index = 1;
            foreach ($params as $value) {
                $stmt->bindValue($param_index, $value);
                $param_index++;
            }
            $stmt->bindValue($param_index, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue($param_index + 1, (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $notifications = $stmt->fetchAll();

            // Convert is_read to boolean
            foreach ($notifications as &$notification) {
                $notification['is_read'] = (bool)$notification['is_read'];
            }

            sendResponse([
                'success' => true,
                'notifications' => $notifications,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to fetch notifications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get unread notification count
     * GET /api/notifications/unread-count
     */
    public function getUnreadCount() {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user['user_id']]);
            $result = $stmt->fetch();

            sendResponse([
                'success' => true,
                'count' => (int)$result['count']
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to fetch unread count: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark notification as read
     * PUT /api/notifications/{id}/read
     */
    public function markAsRead($notification_id) {
        if (!Middleware::validateMethod('PUT')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        try {
            // Verify ownership
            $stmt = $this->pdo->prepare("SELECT user_id FROM notifications WHERE id = ?");
            $stmt->execute([$notification_id]);
            $notification = $stmt->fetch();

            if (!$notification) {
                sendError('Notification not found', 404);
            }

            if (!Middleware::ownsResource($user['user_id'], $notification['user_id'])) {
                sendError('Unauthorized', 403);
            }

            // Mark as read
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            $stmt->execute([$notification_id]);

            sendResponse([
                'success' => true,
                'message' => 'Notification marked as read'
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to mark notification as read: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark all notifications as read
     * PUT /api/notifications/mark-all-read
     */
    public function markAllAsRead() {
        if (!Middleware::validateMethod('PUT')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user['user_id']]);

            sendResponse([
                'success' => true,
                'message' => 'All notifications marked as read'
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to mark all notifications as read: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a notification (internal use)
     * 
     * @param int $user_id User to notify
     * @param int $issue_id Related issue
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $old_status Previous status (optional)
     * @param string $new_status New status (optional)
     * @return bool Success status
     */
    public function createNotification($user_id, $issue_id, $type, $title, $message, $old_status = null, $new_status = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, issue_id, type, title, message, old_status, new_status, is_read)
                VALUES (?, ?, ?, ?, ?, ?, ?, 0)
            ");
            
            $stmt->execute([
                $user_id,
                $issue_id,
                $type,
                $title,
                $message,
                $old_status,
                $new_status
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }
}
?>
