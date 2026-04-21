<?php
/**
 * Authentication and validation middleware
 */

require_once __DIR__ . '/../config/database.php';

class Middleware {
    /**
     * Normalize origin strings for reliable comparisons.
     */
    private static function normalizeOrigin($origin) {
        if (!is_string($origin)) {
            return '';
        }

        $normalized = trim($origin);
        if ($normalized === '') {
            return '';
        }

        // Browsers send origins without trailing slash; normalize env values to match.
        return rtrim($normalized, '/');
    }

    /**
     * Check whether an origin matches an allowlist pattern.
     */
    private static function originMatchesPattern($origin, $pattern) {
        $origin = self::normalizeOrigin($origin);
        $pattern = self::normalizeOrigin($pattern);

        if ($origin === '' || $pattern === '') {
            return false;
        }

        if ($origin === $pattern) {
            return true;
        }

        if (strpos($pattern, '*') === false) {
            return false;
        }

        $escaped = preg_quote($pattern, '#');
        $regex = '#^' . str_replace('\\*', '.*', $escaped) . '$#';
        return (bool)preg_match($regex, $origin);
    }

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
     * CORS headers for cross-origin requests – SIMPLIFIED & BULLETPROOF
     */
    public static function setCORSHeaders() {
        // Explicitly define allowed origins (add your production frontend URL)
        $allowed_origins = [
            'https://civic-connect-topaz-five.vercel.app',   // Your Vercel frontend
            'http://localhost:5173',                         // Local Vue dev server
            'http://localhost:5174',
            'http://localhost:5175',
            'http://localhost:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:3000'
        ];

        // Also respect environment variable if set (optional)
        $envOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
        if (!empty($envOrigins)) {
            $extra = array_map('trim', explode(',', $envOrigins));
            foreach ($extra as $origin) {
                if (!empty($origin)) {
                    $allowed_origins[] = $origin;
                }
            }
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
        header('Access-Control-Max-Age: 3600');

        // Handle preflight OPTIONS request immediately
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
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
