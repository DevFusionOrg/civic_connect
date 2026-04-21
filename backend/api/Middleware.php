<?php
/**
 * Authentication and validation middleware
 */

require_once __DIR__ . '/../config/database.php';

class Middleware {
    /**
     * Check if user is authenticated via token
     * 
     * @return array|null User data if authenticated, null otherwise
     */
    public static function authenticate() {
        global $pdo;
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? null;

        if (!$token) {
            return null;
        }

        // Remove "Bearer " prefix if present
        if (preg_match('/Bearer\s+(.+)/i', $token, $matches)) {
            $token = $matches[1];
        }

        // Check session first
        if (isset($_SESSION['user_id']) && isset($_SESSION['token']) && $_SESSION['token'] === $token) {
            return [
                'user_id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'],
                'token' => $token
            ];
        }

        // For same session requests, just having session data is enough
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            return [
                'user_id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'] ?? null,
                'token' => $token
            ];
        }

        return null;
    }

    /**
     * Require authentication - terminates if not authenticated
     * 
     * @return array User data
     */
    public static function requireAuth() {
        $user = self::authenticate();
        if (!$user) {
            sendError('Unauthorized: Authentication token missing or invalid', 401);
        }
        return $user;
    }

    /**
     * Validate request method
     * 
     * @param string|array $methods Allowed HTTP methods
     * @return bool
     */
    public static function validateMethod($methods) {
        $methods = (array)$methods;
        return in_array($_SERVER['REQUEST_METHOD'], $methods);
    }

    /**
     * Validate required fields in request data
     * 
     * @param array $data Request data
     * @param array $required Required field names
     * @return bool
     */
    public static function validateRequired($data, $required) {
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Log audit trail for user actions
     * 
     * @param int $user_id
     * @param string $action
     * @param string $entity_type
     * @param int $entity_id
     * @param array $old_values
     * @param array $new_values
     * @return bool
     */
    public static function logAuditTrail($user_id, $action, $entity_type, $entity_id, $old_values = null, $new_values = null) {
        global $pdo;

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_trail (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([
                $user_id,
                $action,
                $entity_type,
                $entity_id,
                $old_values ? json_encode($old_values) : null,
                $new_values ? json_encode($new_values) : null,
                $ip_address,
                $user_agent
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * CORS headers for cross-origin requests
     */
    public static function setCORSHeaders() {
        $allowed_origins = [
            $_ENV['APP_URL'] ?? 'http://localhost/civic-connect',
            'http://localhost:5173',  // Vite dev server default
            'http://localhost:5174',  // Vite dev server alternative
            'http://localhost:5175',  // Vite dev server alternative 2
            'http://localhost:5176',  // Vite dev server alternative 3
            'http://localhost:3000',  // Common dev server port
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            'http://127.0.0.1:3000'
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Check if origin is in allowed list
        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            // Fallback to APP_URL for production
            header('Access-Control-Allow-Origin: ' . ($_ENV['APP_URL'] ?? 'http://localhost/civic-connect'));
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 3600');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Verify user owns resource
     * 
     * @param int $user_id Current user ID
     * @param int $resource_user_id Resource owner ID
     * @return bool
     */
    public static function ownsResource($user_id, $resource_user_id) {
        return (int)$user_id === (int)$resource_user_id;
    }

    /**
     * Rate limiting helper (simple implementation)
     * 
     * @param string $key Unique identifier (IP, user_id, etc.)
     * @param int $max_attempts Maximum attempts allowed
     * @param int $window Time window in seconds
     * @return bool True if within limit, false if exceeded
     */
    public static function rateLimit($key, $max_attempts = 5, $window = 60) {
        $cache_key = "ratelimit_" . hash('sha256', $key);
        
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = ['count' => 0, 'reset_time' => time() + $window];
        }

        if (time() > $_SESSION[$cache_key]['reset_time']) {
            $_SESSION[$cache_key] = ['count' => 0, 'reset_time' => time() + $window];
        }

        $_SESSION[$cache_key]['count']++;

        return $_SESSION[$cache_key]['count'] <= $max_attempts;
    }
}
?>
