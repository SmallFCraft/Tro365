<?php
/**
 * Upload API Endpoint
 * Tro365 - File upload handling
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';

use Tro365\Services\Upload;
use Tro365\Core\Auth;

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Require authentication
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verify CSRF token
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid CSRF token'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded or upload error'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $upload = new Upload();
    $type = $_POST['type'] ?? 'general';
    
    // Handle different upload types
    switch ($type) {
        case 'avatar':
            $result = $upload->uploadAvatar($_FILES['file']);
            break;
        case 'post':
            $result = $upload->uploadPostImage($_FILES['file']);
            break;
        default:
            $result = $upload->uploadFile($_FILES['file'], $type);
            break;
    }

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => $result
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Upload failed'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    error_log("Upload API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ], JSON_UNESCAPED_UNICODE);
}
