<?php
require_once __DIR__ . '/bootstrap.php';

$host = $_ENV['DB_HOST'] ?? $_ENV['MYSQLHOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? $_ENV['MYSQLPORT'] ?? '3306';
$db   = $_ENV['DB_NAME'] ?? $_ENV['MYSQLDATABASE'] ?? 'civic_connect';
$user = $_ENV['DB_USER'] ?? $_ENV['MYSQLUSER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? $_ENV['MYSQLPASSWORD'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
