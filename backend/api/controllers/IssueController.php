<?php
/**
 * Issue Controller - Handles CRUD operations for issues
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Middleware.php';
require_once __DIR__ . '/../helpers.php';

class IssueController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Detect pothole from an uploaded image before full issue submission.
     * POST /api/issues/detect-image
     */
    public function detectImage() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        Middleware::requireAuth();

        if (!isset($_FILES['image'])) {
            sendError('No file uploaded', 400);
        }

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            sendError('Image upload failed', 400);
        }

        $upload_result = uploadIssueImage($_FILES['image']);
        if (!$upload_result['success']) {
            sendError($upload_result['error'], 400);
        }

        $image_path = $upload_result['path'];
        $ai_result = detectPotholeFromImage($image_path, 0.5, true);

        // Clean temporary image used only for AI preview.
        deleteImage($image_path);

        $is_pothole = !empty($ai_result['is_pothole']);
        $confidence = (float)($ai_result['confidence'] ?? 0);
        $annotated_image_url = null;
        if (!empty($ai_result['annotated_image_path'])) {
            $annotated_image_url = buildPublicAssetUrl($ai_result['annotated_image_path']);
        }

        sendResponse([
            'success' => true,
            'ai_detection' => $is_pothole ? 'pothole' : 'none',
            'confidence' => $confidence,
            'ai_auto_filled' => $is_pothole,
            'suggested_category' => $is_pothole ? 'roads' : null,
            'suggested_priority' => $is_pothole ? 'high' : null,
            'annotated_image_url' => $annotated_image_url,
            'ai_error' => $ai_result['error'] ?? null,
        ], 200);
    }

    /**
     * Get latest assigned staff for an issue from issue_updates.
     */
    private function getAssignedStaffForIssue($issue_id) {
        $stmt = $this->pdo->prepare("\n            SELECT u.id, u.first_name, u.last_name, u.email\n            FROM issue_updates iu\n            JOIN users u ON iu.user_id = u.id\n            WHERE iu.issue_id = ?\n              AND iu.update_type = 'assigned'\n              AND u.role = 'staff'\n            ORDER BY iu.created_at DESC, iu.id DESC\n            LIMIT 1\n        ");
        $stmt->execute([$issue_id]);
        return $stmt->fetch();
    }

    /**
     * Create a new issue
     * POST /api/issues
     */
    public function create() {
        if (!Middleware::validateMethod('POST')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();
        $data = getRequestData();

        // Validate required fields
        $required = ['title', 'description', 'category'];
        if (!Middleware::validateRequired($data, $required)) {
            sendError('Missing required fields: ' . implode(', ', $required), 400);
        }

        // Validate title length
        if (strlen($data['title']) < 5 || strlen($data['title']) > 255) {
            sendError('Title must be between 5 and 255 characters', 400);
        }

        // Validate description length
        if (strlen($data['description']) < 10) {
            sendError('Description must be at least 10 characters long', 400);
        }

        // Handle image upload if present
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                sendError('Image upload failed', 400);
            }
            
            $upload_result = uploadIssueImage($_FILES['image']);
            if (!$upload_result['success']) {
                sendError($upload_result['error'], 400);
            }
            
            $image_path = $upload_result['path'];
        }

        // AI pothole detection only for road issues.
        $ai_detection = 'none';
        $ai_confidence = 0;
        $ai_auto_filled = false;
        $ai_error = null;

        $normalized_category = strtolower(trim((string)$data['category']));
        if ($normalized_category === 'road') {
            $normalized_category = 'roads';
            $data['category'] = 'roads';
        }

        if ($image_path && $data['category'] === 'roads') {
            $ai_result = detectPotholeFromImage($image_path, 0.5);
            $ai_confidence = (float)($ai_result['confidence'] ?? 0);
            $ai_error = $ai_result['error'] ?? null;

            if (!empty($ai_result['is_pothole'])) {
                $ai_detection = 'pothole';
                // Keep category aligned with existing internal taxonomy.
                $data['category'] = 'roads';
                $data['priority'] = 'high';
                $ai_auto_filled = true;
            }
        }

        try {
            // Get assigned staff based on category
            $staff_stmt = $this->pdo->prepare("
                SELECT staff_id, department_name FROM category_staff_mapping 
                WHERE category = ?
            ");
            $staff_stmt->execute([$data['category']]);
            $assigned_staff = $staff_stmt->fetch();
            $assigned_to = $assigned_staff ? $assigned_staff['staff_id'] : null;

            $stmt = $this->pdo->prepare("
                INSERT INTO issues (user_id, title, description, category, assigned_to, location, latitude, longitude, priority, status, image_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $priority = $data['priority'] ?? 'medium';
            $status = $data['status'] ?? 'pending_review';
            $location = $data['location'] ?? null;
            $latitude = $data['latitude'] ?? null;
            $longitude = $data['longitude'] ?? null;

            // Validate priority and status
            $valid_priorities = ['low', 'medium', 'high'];
            $valid_statuses = ['pending_review', 'in_progress', 'resolved'];

            if (!in_array($priority, $valid_priorities)) {
                sendError('Invalid priority value', 400);
            }

            if (!in_array($status, $valid_statuses)) {
                sendError('Invalid status value', 400);
            }

            $stmt->execute([
                $user['user_id'],
                $data['title'],
                $data['description'],
                $data['category'],
                $assigned_to,
                $location,
                $latitude,
                $longitude,
                $priority,
                $status,
                $image_path
            ]);

            $issue_id = $this->pdo->lastInsertId();

            // Log audit trail
            $audit_data = [
                'title' => $data['title'],
                'category' => $data['category'],
                'priority' => $priority
            ];
            if ($image_path) {
                $audit_data['image_uploaded'] = true;
            }
            if ($assigned_staff) {
                $audit_data['auto_assigned_to'] = $assigned_staff['department_name'];
            }
            $audit_data['ai_detection'] = $ai_detection;
            $audit_data['ai_confidence'] = $ai_confidence;
            if ($ai_error) {
                $audit_data['ai_error'] = $ai_error;
            }
            
            Middleware::logAuditTrail($user['user_id'], 'ISSUE_CREATED', 'issues', $issue_id, null, $audit_data);

            // Log assignment in issue_updates if assigned
            if ($assigned_to) {
                $update_stmt = $this->pdo->prepare("
                    INSERT INTO issue_updates (issue_id, user_id, update_type, content)
                    VALUES (?, ?, 'assigned', ?)
                ");
                $update_stmt->execute([
                    $issue_id,
                    $assigned_to,
                    'Automatically assigned to ' . $assigned_staff['department_name']
                ]);
            }

            sendResponse([
                'success' => true,
                'message' => 'Issue created successfully',
                'issue_id' => $issue_id,
                'ai_detection' => $ai_detection,
                'confidence' => $ai_confidence,
                'ai_auto_filled' => $ai_auto_filled
            ], 201);
        } catch (PDOException $e) {
            sendError('Failed to create issue: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a single issue
     * GET /api/issues/{id}
     */
    public function getIssue($issue_id) {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        try {
            // Try to get current user if authenticated (optional for this endpoint)
            $current_user = Middleware::authenticate();

            $stmt = $this->pdo->prepare("
                SELECT
                    i.*,
                    u.first_name,
                    u.last_name,
                    s.id as assigned_staff_id,
                    s.first_name as assigned_staff_first_name,
                    s.last_name as assigned_staff_last_name,
                    s.email as assigned_staff_email,
                    (SELECT COUNT(*) FROM upvotes WHERE issue_id = i.id) as upvote_count
                FROM issues i
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN users s ON i.assigned_to = s.id
                WHERE i.id = ?
            ");
            $stmt->execute([$issue_id]);
            $issue = $stmt->fetch();

            if (!$issue) {
                sendError('Issue not found', 404);
            }

            // Check if current user has upvoted
            $user_has_upvoted = false;
            if ($current_user) {
                $upvote_stmt = $this->pdo->prepare("
                    SELECT id FROM upvotes 
                    WHERE issue_id = ? AND user_id = ?
                ");
                $upvote_stmt->execute([$issue_id, $current_user['user_id']]);
                $user_has_upvoted = (bool)$upvote_stmt->fetch();
            }
            $issue['user_has_upvoted'] = $user_has_upvoted;

            // Add image_url from image_path
            if (!empty($issue['image_path'])) {
                $issue['image_url'] = buildPublicAssetUrl($issue['image_path']);
            } else {
                $issue['image_url'] = null;
            }

            // Add user_name field
            $issue['user_name'] = trim($issue['first_name'] . ' ' . $issue['last_name']);

            // Add latest assigned staff info
            $assigned_staff = $this->getAssignedStaffForIssue($issue_id);
            if ($assigned_staff) {
                $issue['assigned_staff_id'] = (int)$assigned_staff['id'];
                $issue['assigned_staff_name'] = trim($assigned_staff['first_name'] . ' ' . $assigned_staff['last_name']);
                $issue['assigned_staff_email'] = $assigned_staff['email'];
            } else {
                $issue['assigned_staff_id'] = null;
                $issue['assigned_staff_name'] = null;
                $issue['assigned_staff_email'] = null;
            }

            // Convert numeric fields to proper types
            if ($issue['latitude'] !== null) {
                $issue['latitude'] = (float)$issue['latitude'];
            }
            if ($issue['longitude'] !== null) {
                $issue['longitude'] = (float)$issue['longitude'];
            }
            $issue['upvote_count'] = (int)$issue['upvote_count'];

            sendResponse([
                'success' => true,
                'issue' => $issue
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to fetch issue: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all issues with filters and pagination
     * GET /api/issues
     */
    public function listIssues() {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        // Try to get current user if authenticated (optional)
        $current_user = Middleware::authenticate();

        // Get query parameters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(100, (int)$_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        $category = $_GET['category'] ?? null;
        $status = $_GET['status'] ?? null;
        $priority = $_GET['priority'] ?? null;
        $search = $_GET['search'] ?? null;
        $sort_by = $_GET['sort_by'] ?? 'created_at';
        $sort_order = ($_GET['sort_order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $assigned_to_me = isset($_GET['assigned_to_me']) && $_GET['assigned_to_me'] === 'true';

        // Validate sort parameters
        $allowed_sorts = ['created_at', 'upvote_count', 'title', 'priority'];
        if (!in_array($sort_by, $allowed_sorts)) {
            $sort_by = 'created_at';
        }

        try {
            $where = ['1=1'];
            $params = [];

            if ($category) {
                $where[] = 'i.category = ?';
                $params[] = $category;
            }

            if ($status) {
                $where[] = 'i.status = ?';
                $params[] = $status;
            }

            if ($priority) {
                $where[] = 'i.priority = ?';
                $params[] = $priority;
            }

            if ($search) {
                $where[] = '(i.title LIKE ? OR i.description LIKE ?)';
                $search_term = "%$search%";
                $params[] = $search_term;
                $params[] = $search_term;
            }

            // Filter for issues assigned to current user
            if ($assigned_to_me && $current_user) {
                $where[] = "i.id IN (
                    SELECT issue_id FROM issue_updates 
                    WHERE user_id = ? AND update_type = 'assigned'
                    AND (created_at, id) = (
                        SELECT MAX(created_at), MAX(id) 
                        FROM issue_updates iu2 
                        WHERE iu2.issue_id = issue_updates.issue_id 
                        AND iu2.update_type = 'assigned'
                    )
                )";
                $params[] = $current_user['user_id'];
            }

            $where_clause = implode(' AND ', $where);

            // Get total count
            $count_stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total
                FROM issues i
                WHERE $where_clause
            ");
            $count_stmt->execute($params);
            $total = $count_stmt->fetch()['total'];

            // Get issues
            // Base selection
            $select_fields = "
                i.*,
                u.first_name,
                u.last_name,
                s.id as assigned_staff_id,
                s.first_name as assigned_staff_first_name,
                s.last_name as assigned_staff_last_name,
                s.email as assigned_staff_email,
                (SELECT COUNT(*) FROM upvotes WHERE issue_id = i.id) as upvote_count
            ";
            
            // Add user_has_upvoted if authenticated
            if ($current_user) {
                $select_fields .= ", (SELECT COUNT(*) FROM upvotes WHERE issue_id = i.id AND user_id = ?) as user_has_upvoted";
                // Prepend user_id to params for the select subquery binding if we were binding there, 
                // but we are binding via execute parameters. 
                // Actually, strict positional binding with the current complicated WHERE clause building makes adding a param to the SELECT clause tricky if using ? 
                // because the SELECT comes *before* the WHERE params in syntax but we bind them in order? 
                // No, execute([p1, p2]) binds in order of appearance in the SQL string.
                // The SELECT clause appears first. So we must put the user_id at the BEGINNING of params array.
                array_unshift($params, $current_user['user_id']);
            } else {
                $select_fields .= ", 0 as user_has_upvoted";
            }

            $stmt = $this->pdo->prepare("
                SELECT
                    $select_fields
                FROM issues i
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN users s ON i.assigned_to = s.id
                WHERE $where_clause
                ORDER BY i.$sort_by $sort_order
                LIMIT ? OFFSET ?
            ");

            // Bind WHERE clause parameters
            $param_index = 1;
            foreach ($params as $value) {
                $stmt->bindValue($param_index, $value);
                $param_index++;
            }
            
            // Bind LIMIT and OFFSET as integers
            $stmt->bindValue($param_index, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue($param_index + 1, (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $issues = $stmt->fetchAll();

            // Cast user_has_upvoted to boolean
            foreach ($issues as &$issue) {
                $issue['user_has_upvoted'] = (bool)$issue['user_has_upvoted'];
                $issue['upvote_count'] = (int)$issue['upvote_count'];
            }

            // Process issues (remove sensitive data, add image URLs)
            foreach ($issues as &$issue) {
                // Add image_url
                if (!empty($issue['image_path'])) {
                    $issue['image_url'] = buildPublicAssetUrl($issue['image_path']);
                } else {
                    $issue['image_url'] = null;
                }

                // Add user_name field
                $issue['user_name'] = trim($issue['first_name'] . ' ' . $issue['last_name']);

                // Add assigned staff name from joined fields
                if (!empty($issue['assigned_staff_id'])) {
                    $issue['assigned_staff_name'] = trim($issue['assigned_staff_first_name'] . ' ' . $issue['assigned_staff_last_name']);
                } else {
                    $issue['assigned_staff_name'] = null;
                }
                
                // Clean up temporary fields
                unset($issue['assigned_staff_first_name']);
                unset($issue['assigned_staff_last_name']);
            }

            sendResponse([
                'success' => true,
                'issues' => $issues,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to fetch issues: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an issue
     * PUT /api/issues/{id}
     */
    public function update($issue_id) {
        if (!Middleware::validateMethod('PUT')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();
        $data = getRequestData();

        try {
            // Get current issue with old status
            $stmt = $this->pdo->prepare("SELECT user_id, status FROM issues WHERE id = ?");
            $stmt->execute([$issue_id]);
            $issue = $stmt->fetch();

            if (!$issue) {
                sendError('Issue not found', 404);
            }

            // Store old status for comparison
            $old_status = $issue['status'];

            // Check ownership or role
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user['user_id']]);
            $user_role = $stmt->fetchColumn();

            if (!Middleware::ownsResource($user['user_id'], $issue['user_id']) && !in_array($user_role, ['admin', 'staff'])) {
                sendError('Unauthorized: Cannot update other user\'s issues', 403);
            }

            $allowed_fields = ['title', 'description', 'category', 'location', 'latitude', 'longitude', 'priority', 'status'];
            $update_fields = [];
            $update_values = [];

            // Admin-only assignment of issue to staff.
            if (isset($data['assigned_staff_id'])) {
                if ($user_role !== 'admin') {
                    sendError('Unauthorized: Only admins can assign staff', 403);
                }

                $assigned_staff_id = (int)$data['assigned_staff_id'];
                if ($assigned_staff_id <= 0) {
                    sendError('Invalid staff selection', 400);
                }

                $staff_stmt = $this->pdo->prepare("\n                    SELECT id, first_name, last_name\n                    FROM users\n                    WHERE id = ? AND role = 'staff' AND is_active = 1\n                ");
                $staff_stmt->execute([$assigned_staff_id]);
                $staff = $staff_stmt->fetch();

                if (!$staff) {
                    sendError('Selected staff member not found or inactive', 404);
                }

                $assign_payload = json_encode([
                    'assigned_by' => $user['user_id'],
                    'assigned_staff_id' => $assigned_staff_id,
                    'assigned_staff_name' => trim($staff['first_name'] . ' ' . $staff['last_name']),
                ]);

                $assign_stmt = $this->pdo->prepare("\n                    INSERT INTO issue_updates (issue_id, user_id, update_type, content)\n                    VALUES (?, ?, 'assigned', ?)\n                ");
                $assign_stmt->execute([$issue_id, $assigned_staff_id, $assign_payload]);

                // Update assigned_to column
                $update_fields[] = "i.assigned_to = ?";
                $update_values[] = $assigned_staff_id;

                Middleware::logAuditTrail(
                    $user['user_id'],
                    'ISSUE_ASSIGNED',
                    'issues',
                    $issue_id,
                    null,
                    [
                        'assigned_staff_id' => $assigned_staff_id,
                        'assigned_staff_name' => trim($staff['first_name'] . ' ' . $staff['last_name']),
                    ]
                );
            }

            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    // Validate title and description if provided
                    if ($field === 'title' && (strlen($data[$field]) < 5 || strlen($data[$field]) > 255)) {
                        sendError('Title must be between 5 and 255 characters', 400);
                    }

                    if ($field === 'description' && strlen($data[$field]) < 10) {
                        sendError('Description must be at least 10 characters long', 400);
                    }

                    // Validate priority and status enums
                    if ($field === 'priority' && !in_array($data[$field], ['low', 'medium', 'high'])) {
                        sendError('Invalid priority value', 400);
                    }

                    if ($field === 'status' && !in_array($data[$field], ['pending_review', 'in_progress', 'resolved'])) {
                        sendError('Invalid status value', 400);
                    }

                    $update_fields[] = "i.$field = ?";
                    $update_values[] = $data[$field];
                }
            }

            // Handle resolved_at timestamp
            if (isset($data['status'])) {
                if ($data['status'] === 'resolved' && $old_status !== 'resolved') {
                    // Set resolved_at to current timestamp when status changes to resolved
                    $update_fields[] = "i.resolved_at = NOW()";
                } elseif ($data['status'] !== 'resolved' && $old_status === 'resolved') {
                    // Clear resolved_at when status changes away from resolved
                    $update_fields[] = "i.resolved_at = NULL";
                }
            }

            if (empty($update_fields)) {
                sendError('No fields to update', 400);
            }

            $update_values[] = $issue_id;

            $stmt = $this->pdo->prepare("
                UPDATE issues i
                SET " . implode(', ', $update_fields) . "
                WHERE i.id = ?
            ");
            $stmt->execute($update_values);

            // Log audit trail
            Middleware::logAuditTrail($user['user_id'], 'ISSUE_UPDATED', 'issues', $issue_id, null, $data);

            // Check if status was changed and send notification
            if (isset($data['status']) && $old_status !== $data['status']) {
                // Get issue details and user info for notification
                $issue_stmt = $this->pdo->prepare("
                    SELECT i.*, u.email, u.first_name, u.last_name
                    FROM issues i
                    JOIN users u ON i.user_id = u.id
                    WHERE i.id = ?
                ");
                $issue_stmt->execute([$issue_id]);
                $issue_details = $issue_stmt->fetch();
                
                if ($issue_details) {
                    // Create in-app notification
                    require_once __DIR__ . '/NotificationController.php';
                    $notificationController = new NotificationController();
                    
                    $status_labels = [
                        'pending_review' => 'Pending Review',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved'
                    ];
                    
                    $old_label = $status_labels[$old_status] ?? $old_status;
                    $new_label = $status_labels[$data['status']] ?? $data['status'];
                    
                    $notificationController->createNotification(
                        $issue_details['user_id'],
                        $issue_id,
                        'status_change',
                        'Issue Status Updated',
                        "Your issue '{$issue_details['title']}' status changed from {$old_label} to {$new_label}",
                        $old_status,
                        $data['status']
                    );
                    
                    // Send email notification
                    sendStatusChangeEmail(
                        $issue_details['email'],
                        trim($issue_details['first_name'] . ' ' . $issue_details['last_name']),
                        [
                            'id' => $issue_id,
                            'title' => $issue_details['title'],
                            'category' => $issue_details['category']
                        ],
                        $old_status,
                        $data['status']
                    );
                }
            }

            sendResponse([
                'success' => true,
                'message' => 'Issue updated successfully'
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to update issue: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete an issue
     * DELETE /api/issues/{id}
     */
    public function delete($issue_id) {
        if (!Middleware::validateMethod('DELETE')) {
            sendError('Method not allowed', 405);
        }

        $user = Middleware::requireAuth();

        try {
            // Get issue
            $stmt = $this->pdo->prepare("SELECT user_id, image_path FROM issues WHERE id = ?");
            $stmt->execute([$issue_id]);
            $issue = $stmt->fetch();

            if (!$issue) {
                sendError('Issue not found', 404);
            }

            // Check ownership or role
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user['user_id']]);
            $user_role = $stmt->fetchColumn();

            if (!Middleware::ownsResource($user['user_id'], $issue['user_id']) && !in_array($user_role, ['admin', 'staff'])) {
                sendError('Unauthorized: Cannot delete other user\'s issues', 403);
            }

            // Delete image if exists
            if ($issue['image_path']) {
                deleteImage($issue['image_path']);
            }

            // Delete issue (cascade delete handled by DB)
            $stmt = $this->pdo->prepare("DELETE FROM issues WHERE id = ?");
            $stmt->execute([$issue_id]);

            // Log audit trail
            Middleware::logAuditTrail($user['user_id'], 'ISSUE_DELETED', 'issues', $issue_id);

            sendResponse([
                'success' => true,
                'message' => 'Issue deleted successfully'
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to delete issue: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get issues by user
     * GET /api/users/{id}/issues
     */
    public function getUserIssues($user_id) {
        if (!Middleware::validateMethod('GET')) {
            sendError('Method not allowed', 405);
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, (int)$_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;

        // Try to get current user if authenticated
        $current_user = Middleware::authenticate();

        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total
                FROM issues
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $total = $stmt->fetch()['total'];

            // Base selection
            $select_fields = "*, (SELECT COUNT(*) FROM upvotes WHERE issue_id = issues.id) as upvote_count";

            // Add user_has_upvoted if authenticated/viewing own issues
            // Since we know the user_id of the issues, if $current_user['user_id'] == $user_id, we can check if they upvoted.
            // But getUserIssues might be viewed by others? The route is /users/{id}/issues.
            // Assuming strict consistent behavior with listIssues.
            
            $params = [];
            if ($current_user) {
                $select_fields .= ", (SELECT COUNT(*) FROM upvotes WHERE issue_id = issues.id AND user_id = ?) as user_has_upvoted";
                $params[] = $current_user['user_id'];
            } else {
                $select_fields .= ", 0 as user_has_upvoted";
            }
            
            $params[] = $user_id; // For WHERE clause

            $stmt = $this->pdo->prepare("
                SELECT $select_fields
                FROM issues
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            // Bind parameters
            $param_index = 1;
            foreach ($params as $value) {
                $stmt->bindValue($param_index, $value);
                $param_index++;
            }
            
            // Bind LIMIT and OFFSET as integers
            $stmt->bindValue($param_index, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue($param_index + 1, (int)$offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $issues = $stmt->fetchAll();

            // Add image URLs and cast types
            foreach ($issues as &$issue) {
                if (!empty($issue['image_path'])) {
                    $issue['image_url'] = buildPublicAssetUrl($issue['image_path']);
                } else {
                    $issue['image_url'] = null;
                }
                
                $issue['user_has_upvoted'] = (bool)($issue['user_has_upvoted'] ?? false);
                $issue['upvote_count'] = (int)($issue['upvote_count'] ?? 0);
            }

            sendResponse([
                'success' => true,
                'issues' => $issues,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ], 200);
        } catch (PDOException $e) {
            sendError('Failed to fetch user issues: ' . $e->getMessage(), 500);
        }
    }
}
?>
