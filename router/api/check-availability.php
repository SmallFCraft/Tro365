<?php
/**
 * Check Availability API Endpoint
 * Tro365 - Standardized email/username availability checking
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';

use Tro365\Api\CheckAvailabilityAPI;

try {
    $api = new CheckAvailabilityAPI();
    $api->handle();
} catch (Exception $e) {
    error_log("Check Availability API Critical Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ], JSON_UNESCAPED_UNICODE);
}
