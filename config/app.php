<?php
/**
 * Application Configuration
 * Tro365 - Website thuê trọ
 */

// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Application settings
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Trọ 365');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8000/');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Asia/Ho_Chi_Minh');

// Security settings
define('APP_KEY', $_ENV['APP_KEY'] ?? 'your-secret-key-here');
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 7200));
define('CSRF_TOKEN_NAME', $_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_token');

// Upload settings
define('UPLOAD_MAX_SIZE', (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 5242880)); // 5MB
define('UPLOAD_ALLOWED_TYPES', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,gif,webp,pdf,doc,docx');
define('UPLOAD_PATH', rtrim($_ENV['UPLOAD_PATH'] ?? 'assets/uploads', '/') . '/');

// Commission settings
define('COMMISSION_RATE', (float)($_ENV['COMMISSION_RATE'] ?? 5.0));

// Pagination settings
define('POSTS_PER_PAGE', (int)($_ENV['POSTS_PER_PAGE'] ?? 20));
define('ADMIN_POSTS_PER_PAGE', (int)($_ENV['ADMIN_POSTS_PER_PAGE'] ?? 50));

// Cache settings
define('CACHE_ENABLED', filter_var($_ENV['CACHE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('CACHE_LIFETIME', (int)($_ENV['CACHE_LIFETIME'] ?? 3600));

// Logging settings
// Always resolve LOG_PATH to an absolute path inside the project root by default
// to avoid open_basedir issues on shared hosting (e.g., DirectAdmin)
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'info');
$__defaultLogPath = rtrim($_ENV['LOG_PATH'] ?? (dirname(__DIR__) . '/logs'), '/');
// Normalize to absolute path if a relative path was provided
if (strpos($__defaultLogPath, '/') !== 0) {
    $__defaultLogPath = rtrim(dirname(__DIR__) . '/' . $__defaultLogPath, '/');
}
define('LOG_PATH', $__defaultLogPath . '/');

// Contact information
define('COMPANY_NAME', $_ENV['COMPANY_NAME'] ?? 'Công ty TNHH Trọ 365');
define('COMPANY_ADDRESS', $_ENV['COMPANY_ADDRESS'] ?? 'Hà Nội, Việt Nam');
define('COMPANY_PHONE', $_ENV['COMPANY_PHONE'] ?? '1900xxxx');
define('COMPANY_EMAIL', $_ENV['COMPANY_EMAIL'] ?? 'contact@tro365.com');
define('HOTLINE', $_ENV['HOTLINE'] ?? '1900xxxx');

// Social media
define('FACEBOOK_URL', $_ENV['FACEBOOK_URL'] ?? 'https://facebook.com/tro365');
define('ZALO_URL', $_ENV['ZALO_URL'] ?? 'https://zalo.me/tro365');
define('YOUTUBE_URL', $_ENV['YOUTUBE_URL'] ?? '');
define('INSTAGRAM_URL', $_ENV['INSTAGRAM_URL'] ?? '');

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token generation
if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// Helper function to get CSRF token
function csrf_token()
{
    return $_SESSION[CSRF_TOKEN_NAME] ?? '';
}

// Helper function to verify CSRF token
function verify_csrf_token($token)
{
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

// Helper function to get app URL
function app_url($path = '')
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

// Helper function to get asset URL
function asset_url($path = '')
{
    return app_url('assets/' . ltrim($path, '/'));
}

// Helper function to redirect
function redirect($url, $statusCode = 302)
{
    header('Location: ' . $url, true, $statusCode);
    exit;
}

// Opportunistic, automatic cache pruning (very cheap)
if (function_exists('sys_auto_prune_cache')) {
    sys_auto_prune_cache();
}

// Helper function to get current URL
function current_url()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// Create upload directories if they don't exist (use absolute paths)
$baseDir = dirname(__DIR__); // Get project root directory
$uploadDirs = [
    $baseDir . '/assets/uploads',
    $baseDir . '/assets/uploads/posts',
    $baseDir . '/assets/uploads/avatars',
    $baseDir . '/assets/uploads/documents',
    $baseDir . '/assets/uploads/temp'
];

foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Load constants and helpers
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../includes/functions/helpers.php';

/**
 * Get TinyMCE API Key from configuration
 */
function getTinyMCEApiKey() {
    static $apiKey = null;

    if ($apiKey === null) {
        try {
            $config = new \Tro365\Core\Config();
            $apiKey = $config->getValue('tinymce_api_key', 'no-api-key');
        } catch (Exception $e) {
            $apiKey = 'no-api-key';
        }
    }

    return $apiKey;
}

/**
 * Get maximum rooms per post from configuration
 */
function getMaxRoomsPerPost() {
    static $maxRooms = null;

    if ($maxRooms === null) {
        try {
            $config = new \Tro365\Core\Config();
            $maxRooms = intval($config->getValue('max_rooms_per_post', '50'));

            // Ensure valid range
            if ($maxRooms < 1 || $maxRooms > 1000) {
                $maxRooms = 50;
            }
        } catch (Exception $e) {
            $maxRooms = 50;
        }
    }

    return $maxRooms;
}

// PSR-4 backward-compatibility class aliases (old classes moved to proper namespaces)
// Map Old class -> New namespace for backward compatibility
if (!class_exists('Tro365\\Contact') && class_exists('Tro365\\Models\\Contact')) {
    class_alias('Tro365\\Models\\Contact', 'Tro365\\Contact');
}
if (!class_exists('Tro365\\Activity') && class_exists('Tro365\\Models\\Activity')) {
    class_alias('Tro365\\Models\\Activity', 'Tro365\\Activity');
}
if (!class_exists('Tro365\\DataConsistency') && class_exists('Tro365\\Services\\DataConsistencyService')) {
    class_alias('Tro365\\Services\\DataConsistencyService', 'Tro365\\DataConsistency');
}
if (!class_exists('Tro365\\SettingsController') && class_exists('Tro365\\Controllers\\SettingsController')) {
    class_alias('Tro365\\Controllers\\SettingsController', 'Tro365\\SettingsController');
}
if (!class_exists('Tro365\\DebugManager') && class_exists('Tro365\\Services\\DebugManager')) {
    class_alias('Tro365\\Services\\DebugManager', 'Tro365\\DebugManager');
}

