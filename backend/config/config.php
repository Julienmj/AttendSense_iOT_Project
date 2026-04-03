<?php
/**
 * AttendSense API Configuration
 * Database connection and API settings
 */

// Database Configuration
// TODO: Update these values with your actual database credentials
// define('DB_HOST', 'localhost');           // Not needed for SQLite
// define('DB_NAME', 'attendsense');         // Not needed for SQLite
// define('DB_USER', 'root');               // Not needed for SQLite
// define('DB_PASS', '');  // Not needed for SQLite
// define('DB_CHARSET', 'utf8mb4');         // Not needed for SQLite

// SQLite Database Configuration
define('DB_FILE', __DIR__ . '/../../database/attendsense.sqlite');

// API Configuration
define('API_BASE_URL', 'http://localhost/attendsense/backend/api');
define('CORS_ORIGIN', '*'); // Set to your frontend domain in production

// Security
// TODO: Generate a secure random JWT secret key
define('JWT_SECRET', 'feed-world-width');  // CHANGE THIS to a random string!
define('HASH_ALGO', 'PASSWORD_DEFAULT');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS Headers - allow Netlify frontend and local dev
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:3000',
    getenv('FRONTEND_URL') ?: 'https://attendsense.netlify.app'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins) || str_ends_with($origin, '.netlify.app')) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database Connection Class
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            // Create SQLite database directory if it doesn't exist
            $dbDir = dirname(DB_FILE);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            $this->connection = new PDO('sqlite:' . DB_FILE);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable foreign keys
            $this->connection->exec('PRAGMA foreign_keys = ON;');

            // Optimize SQLite settings
            $this->connection->exec('PRAGMA journal_mode = WAL;');
            $this->connection->exec('PRAGMA synchronous = NORMAL;');
            $this->connection->exec('PRAGMA cache_size = 1000;');
            $this->connection->exec('PRAGMA temp_store = memory;');

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'SQLite database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}

// API Response Helper
class ApiResponse {
    public static function success($data = null, $message = 'Success') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    public static function error($message, $code = 400, $extra = []) {
        http_response_code($code);
        $response = ['success' => false, 'error' => $message];
        if (!empty($extra)) $response = array_merge($response, $extra);
        echo json_encode($response);
        exit;
    }

    public static function created($data = null, $message = 'Resource created successfully') {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

// JWT Helper (Simple implementation - use firebase/php-jwt in production)
class JWT {
    public static function encode($payload, $secret, $alg = 'HS256') {
        $header = json_encode(['typ' => 'JWT', 'alg' => $alg]);
        $payload = json_encode($payload);
        
        $headerEncoded = self::base64UrlEncode($header);
        $payloadEncoded = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }

    public static function decode($token, $secret) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        $header = self::base64UrlDecode($parts[0]);
        $payload = self::base64UrlDecode($parts[1]);
        $signature = self::base64UrlDecode($parts[2]);
        
        $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        return json_decode($payload, true);
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

// Authentication Middleware
function requireAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        ApiResponse::error('Authorization token required', 401);
    }
    
    $token = $matches[1];
    $payload = JWT::decode($token, JWT_SECRET);
    
    if (!$payload) {
        ApiResponse::error('Invalid or expired token', 401);
    }
    
    return $payload;
}

// Input Validation
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $fieldRules) {
        $value = $data[$field] ?? null;
        
        if (in_array('required', $fieldRules) && empty($value)) {
            $errors[$field] = ucfirst($field) . ' is required';
            continue;
        }
        
        if (empty($value)) continue;
        
        if (in_array('email', $fieldRules) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = 'Invalid email format';
        }
        
        if (in_array('mac_address', $fieldRules) && !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value)) {
            $errors[$field] = 'Invalid MAC address format';
        }
        
        if (isset($fieldRules['min']) && strlen($value) < $fieldRules['min']) {
            $errors[$field] = ucfirst($field) . ' must be at least ' . $fieldRules['min'] . ' characters';
        }
        
        if (isset($fieldRules['max']) && strlen($value) > $fieldRules['max']) {
            $errors[$field] = ucfirst($field) . ' must not exceed ' . $fieldRules['max'] . ' characters';
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Validation failed', 'errors' => $errors]);
        exit;
    }
    
    return $data;
}

// Get JSON input
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}
?>
