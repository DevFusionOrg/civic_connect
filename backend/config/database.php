<?php
require_once __DIR__ . '/bootstrap.php';

$driver = strtolower($_ENV['DB_DRIVER'] ?? 'mysql');
$host = $_ENV['DB_HOST'] ?? $_ENV['PGHOST'] ?? $_ENV['MYSQLHOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? $_ENV['PGPORT'] ?? $_ENV['MYSQLPORT'] ?? ($driver === 'pgsql' ? '5432' : '3306');
$db   = $_ENV['DB_NAME'] ?? $_ENV['PGDATABASE'] ?? $_ENV['MYSQLDATABASE'] ?? 'civic_connect';
$user = $_ENV['DB_USER'] ?? $_ENV['PGUSER'] ?? $_ENV['MYSQLUSER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? $_ENV['PGPASSWORD'] ?? $_ENV['MYSQLPASSWORD'] ?? '';
$sslmode = $_ENV['DB_SSLMODE'] ?? 'require';

$dsn = '';
if ($driver === 'pgsql' || $driver === 'postgres' || $driver === 'postgresql') {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$sslmode";
} else {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
}

try {
    $pdo = new PDO(
        $dsn,
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
