<?php
/**
 * Notifications API Endpoint
 * Tro365 - Standardized notifications management
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';

use Tro365\Api\NotificationsAPI;

try {
    $api = new NotificationsAPI();
    $api->handle();
} catch (Exception $e) {
    error_log("Notifications API Critical Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ], JSON_UNESCAPED_UNICODE);
}

