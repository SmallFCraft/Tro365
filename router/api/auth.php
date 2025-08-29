<?php
/**
 * API Routes: Authentication
 * Tro365 - Website thuê trọ
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';

use Tro365\Core\Auth;
use Tro365\Helpers\LoggerHelper;

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Get the action from URL path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Extract action from path: /api/auth/{action}
$action = $pathParts[2] ?? '';

try {
    switch ($action) {
        case 'refresh-session':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $auth = new Auth();

            // Debug logging for session refresh
            logAPI("Session refresh attempt", [
                'session_id' => session_id(),
                'user_id' => $_SESSION['user_id'] ?? null,
                'session_data' => array_keys($_SESSION),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Check if user is logged in
            if (!$auth->isLoggedIn()) {
                logAPI("Session refresh failed - not authenticated", [
                    'session_id' => session_id(),
                    'session_data' => $_SESSION
                ]);
                http_response_code(401);
                echo json_encode(['error' => 'Not authenticated']);
                exit;
            }
            
            // Force refresh session data
            $result = $auth->updateSession();

            // Log the session refresh
            LoggerHelper::logAuth('session_refresh', [
                'user_id' => $auth->getCurrentUser()['ID'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            if ($result) {
                // Get current user data after refresh
                $currentUser = $auth->getCurrentUser(true); // Force refresh
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Session refreshed successfully',
                    'user' => [
                        'id' => $currentUser['ID'],
                        'username' => $currentUser['TenDN'],
                        'name' => $currentUser['HoTen'],
                        'role' => $currentUser['VaiTroID'],
                        'role_name' => $currentUser['TenVT'],
                        'status' => $currentUser['TrangThai'],
                        'email' => $currentUser['Email'],
                        'phone' => $currentUser['SDT'] ?? '',
                        'avatar' => $currentUser['AnhDaiDien'] ?? ''
                    ],
                    'timestamp' => time()
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Failed to refresh session',
                    'timestamp' => time()
                ]);
            }
            break;
            
        case 'current-user':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $auth = new Auth();
            
            if (!$auth->isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Not authenticated']);
                exit;
            }
            
            $currentUser = $auth->getCurrentUser();
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $currentUser['ID'],
                    'username' => $currentUser['TenDN'],
                    'name' => $currentUser['HoTen'],
                    'role' => $currentUser['VaiTroID'],
                    'role_name' => $currentUser['TenVT']
                ]
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
}
?>
