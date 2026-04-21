<?php
/**
 * Public Statistics API Endpoint
 * Returns platform-wide statistics for the homepage
 * No authentication required - public endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

try {
    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit();
    }

    // Get total issues count
    $issuesQuery = "SELECT COUNT(*) as total FROM issues";
    $issuesStmt = $pdo->prepare($issuesQuery);
    $issuesStmt->execute();
    $totalIssues = $issuesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total users count
    $usersQuery = "SELECT COUNT(*) as total FROM users";
    $usersStmt = $pdo->prepare($usersQuery);
    $usersStmt->execute();
    $totalUsers = $usersStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get resolved issues count
    $resolvedQuery = "SELECT COUNT(*) as total FROM issues WHERE status = 'resolved'";
    $resolvedStmt = $pdo->prepare($resolvedQuery);
    $resolvedStmt->execute();
    $resolvedIssues = $resolvedStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate resolution rate
    $resolutionRate = $totalIssues > 0 
        ? round(($resolvedIssues / $totalIssues) * 100) 
        : 0;

    // Return statistics
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'totalIssues' => (int)$totalIssues,
            'totalUsers' => (int)$totalUsers,
            'resolutionRate' => (int)$resolutionRate
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch statistics',
        'error' => $e->getMessage()
    ]);
}

