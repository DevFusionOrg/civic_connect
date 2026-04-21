<?php
/**
 * Admin Controller - Handles admin-specific functionality
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../helpers.php';

class AdminController {
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
        
        // Also check if user is admin in DB to be safe
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user['user_id']]);
        $dbUser = $stmt->fetch();

        if (!$dbUser || $dbUser['role'] !== 'admin') {
            sendError('Unauthorized: Admin access required', 403);
        }

        return $user;
    }

    /**
     * Get dashboard statistics
     * GET /api/admin/stats
     */
    public function getStats() {
        $this->requireAdmin();

        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        try {
            $stats = [];

            // User stats
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM users");
            $stats['users_total'] = $stmt->fetch()['total'];

            $stmt = $this->pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
            $stats['users_by_role'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Issue stats
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM issues");
            $stats['issues_total'] = $stmt->fetch()['total'];

            $stmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM issues GROUP BY status");
            $stats['issues_by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Recent activity (last 7 days audit logs)
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM audit_trail WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stats['recent_activity_count'] = $stmt->fetch()['total'];

            sendResponse([
                'success' => true,
                'stats' => $stats
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to fetch stats: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all users
     * GET /api/admin/users
     */
    public function getAllUsers() {
        $this->requireAdmin();

        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        try {
            $query = "SELECT id, email, first_name, last_name, role, is_active, created_at, last_login, email_verified FROM users";
            $params = [];

            if (!empty($search)) {
                $query .= " WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
                $searchTerm = "%$search%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }

            $query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll();

            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) FROM users";
            if (!empty($search)) {
                $countQuery .= " WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
            }
            $countStmt = $this->pdo->prepare($countQuery);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            sendResponse([
                'success' => true,
                'users' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'limit' => $limit
                ]
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to fetch users: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update user role
     * PUT /api/admin/users/{id}/role
     */
    public function updateUserRole($userId) {
        $admin = $this->requireAdmin();

        if (!Middleware::validateMethod('PUT')) {
            sendError('Method not allowed', 405);
        }

        $data = getRequestData();
        if (!isset($data['role']) || !in_array($data['role'], ['citizen', 'staff', 'admin'])) {
            sendError('Invalid role', 400);
        }

        // Prevent self-demotion (optional, but good practice to avoid locking oneself out)
        if ($userId == $admin['user_id'] && $data['role'] !== 'admin') {
            sendError('Cannot change your own role', 400);
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$data['role'], $userId]);

            Middleware::logAuditTrail($admin['user_id'], 'ROLE_UPDATE', 'users', $userId, null, ['new_role' => $data['role']]);

            sendResponse([
                'success' => true,
                'message' => 'User role updated successfully'
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to update user role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get audit logs
     * GET /api/admin/audit-logs
     */
    public function getAuditLogs() {
        $this->requireAdmin();

        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = ($page - 1) * $limit;

        try {
            $query = "
                SELECT a.*, u.email as user_email, u.first_name, u.last_name 
                FROM audit_trail a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC 
                LIMIT $limit OFFSET $offset
            ";
            
            $stmt = $this->pdo->query($query);
            $logs = $stmt->fetchAll();

            $total = $this->pdo->query("SELECT COUNT(*) FROM audit_trail")->fetchColumn();

            sendResponse([
                'success' => true,
                'logs' => $logs,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'limit' => $limit
                ]
            ], 200);

        } catch (PDOException $e) {
            sendError('Failed to fetch audit logs: ' . $e->getMessage(), 500);
        }
    }
}
