<?php
/**
 * Handle admin routes
 */
function handleAdminRoutes($parts, $method) {
    // Check if parts is empty, meaning just /api/admin
    if (empty($parts)) {
        sendError('Invalid admin endpoint', 400);
    }

    $controller = new AdminController();
    $action = array_shift($parts);

    switch ($action) {
        case 'stats':
            // GET /api/admin/stats
            $controller->getStats();
            break;

        case 'users':
            // GET /api/admin/users
            if (empty($parts)) {
                $controller->getAllUsers();
            } else {
                $user_id = array_shift($parts);
                $sub_action = array_shift($parts);
                
                if ($sub_action === 'role') {
                    // PUT /api/admin/users/{id}/role
                    $controller->updateUserRole($user_id);
                } else {
                    sendError('Invalid admin users endpoint', 404);
                }
            }
            break;

        case 'audit-logs':
            // GET /api/admin/audit-logs
            $controller->getAuditLogs();
            break;

        case 'staff':
            // Staff management routes
            $staffController = new StaffController();
            
            if (empty($parts)) {
                // GET /api/admin/staff - list all staff
                // POST /api/admin/staff - create new staff
                if ($method === 'GET') {
                    $staffController->getAllStaff();
                } elseif ($method === 'POST') {
                    $staffController->createStaff();
                } else {
                    sendError('Method not allowed', 405);
                }
            } else {
                $staff_id = array_shift($parts);
                $sub_action = array_shift($parts);
                
                if ($sub_action === 'status') {
                    // PUT /api/admin/staff/{id}/status
                    $staffController->toggleStaffStatus($staff_id);
                } elseif (empty($sub_action)) {
                    // GET /api/admin/staff/{id} - get staff details
                    // PUT /api/admin/staff/{id} - update staff
                    if ($method === 'GET') {
                        $staffController->getStaff($staff_id);
                    } elseif ($method === 'PUT') {
                        $staffController->updateStaff($staff_id);
                    } else {
                        sendError('Method not allowed', 405);
                    }
                } else {
                    sendError('Invalid admin staff endpoint', 404);
                }
            }
            break;

        default:
            sendError('Invalid admin endpoint', 404);
    }
}
?>
