<?php
/**
 * Upvote Controller - Handles issue upvoting
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../helpers.php';

class UpvoteController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Add upvote to an issue
     * POST /api/issues/{id}/upvotes
     */
    public function addUpvote($issue_id) {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        try {
            // Check if issue exists
            $stmt = $this->pdo->prepare("SELECT id FROM issues WHERE id = ?");
            $stmt->execute([$issue_id]);
            if (!$stmt->fetch()) {
                sendError('Issue not found', 404);
            }

            // Check if already upvoted
            $stmt = $this->pdo->prepare("
                SELECT id FROM upvotes
                WHERE issue_id = ? AND user_id = ?
            ");
            $stmt->execute([$issue_id, $user['user_id']]);
            if ($stmt->fetch()) {
                sendError('Already upvoted this issue', 409);
            }

            // Add upvote
            $stmt = $this->pdo->prepare("
                INSERT INTO upvotes (issue_id, user_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$issue_id, $user['user_id']]);

            // Update upvote count in issues table
            $stmt = $this->pdo->prepare("
                UPDATE issues
                SET upvote_count = (SELECT COUNT(*) FROM upvotes WHERE issue_id = ?)
                WHERE id = ?
            ");
            $stmt->execute([$issue_id, $issue_id]);

            // Get updated upvote count
            $stmt = $this->pdo->prepare("SELECT upvote_count FROM issues WHERE id = ?");
            $stmt->execute([$issue_id]);
            $upvote_count = $stmt->fetch()['upvote_count'];

            // Log audit trail
            Middleware::logAuditTrail($user['user_id'], 'ISSUE_UPVOTED', 'upvotes', $this->pdo->lastInsertId(), null, [
                'issue_id' => $issue_id
            ]);

            sendResponse([
                'success' => true,
                'message' => 'Issue upvoted successfully',
                'upvote_count' => $upvote_count
            ], 201);
        } catch (PDOException $e) {
            sendError('Failed to add upvote: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove upvote from an issue
     * DELETE /api/issues/{id}/upvotes
     */
    public function removeUpvote($issue_id) {
        if (!Middleware::validateMethod('DELETE')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        try {
            // Check if issue exists
            $stmt = $this->pdo->prepare("SELECT id FROM issues WHERE id = ?");
            $stmt->execute([$issue_id]);
            if (!$stmt->fetch()) {
                sendError('Issue not found', 404);
            }

            // Check if upvoted
            $stmt = $this->pdo->prepare("
                SELECT id FROM upvotes
                WHERE issue_id = ? AND user_id = ?
            ");
            $stmt->execute([$issue_id, $user['user_id']]);
            if (!$stmt->fetch()) {
                sendError('Not upvoted yet', 404);
            }

            // Remove upvote
            $stmt = $this->pdo->prepare("
                DELETE FROM upvotes
                WHERE issue_id = ? AND user_id = ?
            ");
            $stmt->execute([$issue_id, $user['user_id']]);

            // Update upvote count
            $stmt = $this->pdo->prepare("
                UPDATE issues
                SET upvote_count = (SELECT COUNT(*) FROM upvotes WHERE issue_id = ?)
                WHERE id = ?
            ");
            $stmt->execute([$issue_id, $issue_id]);

            // Get updated upvote count
            $stmt = $this->pdo->prepare("SELECT upvote_count FROM issues WHERE id = ?");
            $stmt->execute([$issue_id]);
            $upvote_count = $stmt->fetch()['upvote_count'];

            // Log audit trail
            Middleware::logAuditTrail($user['user_id'], 'ISSUE_UNUPVOTED', 'upvotes', $issue_id);

            sendResponse([
                'success' => true,
                'message' => 'Upvote removed successfully',
                'upvote_count' => $upvote_count
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to remove upvote: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get upvotes for an issue
     * GET /api/issues/{id}/upvotes
     */
    public function getUpvotes($issue_id) {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, (int)$_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;

        try {
            // Check if issue exists
            $stmt = $this->pdo->prepare("SELECT id FROM issues WHERE id = ?");
            $stmt->execute([$issue_id]);
            if (!$stmt->fetch()) {
                sendError('Issue not found', 404);
            }

            // Get total upvotes
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total
                FROM upvotes
                WHERE issue_id = ?
            ");
            $stmt->execute([$issue_id]);
            $total = $stmt->fetch()['total'];

            // Get upvotes with user details
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id as upvote_id,
                    u.user_id,
                    u.created_at,
                    us.first_name,
                    us.last_name
                FROM upvotes u
                LEFT JOIN users us ON u.user_id = us.id
                WHERE u.issue_id = ?
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$issue_id, $limit, $offset]);
            $upvotes = $stmt->fetchAll();

            sendResponse([
                'success' => true,
                'upvotes' => $upvotes,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to fetch upvotes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if current user upvoted issue
     * GET /api/issues/{id}/upvotes/check
     */
    public function checkUpvote($issue_id) {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM upvotes
                WHERE issue_id = ? AND user_id = ?
            ");
            $stmt->execute([$issue_id, $user['user_id']]);
            $upvoted = (bool)$stmt->fetch();

            sendResponse([
                'success' => true,
                'upvoted' => $upvoted
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to check upvote: ' . $e->getMessage(), 500);
        }
    }
}
?>
