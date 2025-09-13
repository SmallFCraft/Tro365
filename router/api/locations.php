<?php
/**
 * Locations API Endpoint
 * Tro365 - Standardized locations management using provinces.open-api.vn
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';

use Tro365\Api\LocationsAPI;

try {
    $api = new LocationsAPI();
    $api->handle();
} catch (Exception $e) {
    error_log("Locations API Critical Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ], JSON_UNESCAPED_UNICODE);
}
