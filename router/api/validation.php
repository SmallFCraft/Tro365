<?php
/**
 * Validation API Endpoint
 * Tro365 - Serves canonical validation patterns for client-side validation
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';

try {
    // Set JSON response header
    header('Content-Type: application/json; charset=utf-8');

    // Handle CORS preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Load validation rules from config
    $configPath = __DIR__ . '/../../config/validation.php';

    if (!file_exists($configPath)) {
        throw new Exception('Validation config file not found');
    }

    $validationRules = require $configPath;

    // Return only the patterns, constraints, and messages needed for client-side validation
    $clientRules = [
        'patterns' => $validationRules['patterns'] ?? [],
        'constraints' => $validationRules['constraints'] ?? [],
        'messages' => $validationRules['messages']['vi'] ?? []
    ];

    echo json_encode($clientRules, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Validation Rules API Critical Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ], JSON_UNESCAPED_UNICODE);
}

