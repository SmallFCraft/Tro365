<?php

namespace Tro365\Core;

use Exception;

/**
 * Base API Class
 * Tro365 - Standardized API responses and error handling
 */
abstract class BaseAPI
{
    protected $db;
    protected $auth;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
        
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');
        
        // Enable CORS if needed
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Send success response
     */
    protected function sendSuccess($data = null, $message = 'Success', $code = 200)
    {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Send error response
     */
    protected function sendError($message = 'Error', $code = 500, $details = null)
    {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (APP_DEBUG && $details) {
            $response['debug'] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequiredFields($data, $required)
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            $this->sendError('Missing required fields: ' . implode(', ', $missing), 400);
        }

        return true;
    }

    /**
     * Enhanced validation using rakit/validation
     */
    protected function validateEnhanced($data, $rules, $messages = [])
    {
        try {
            $validation = \Tro365\Helpers\ValidationHelper::enhancedValidate($data, $rules, $messages);

            if (!$validation['valid']) {
                $errors = [];
                foreach ($validation['errors'] as $field => $fieldErrors) {
                    $errors = array_merge($errors, $fieldErrors);
                }
                $this->sendError('Validation failed: ' . implode(', ', $errors), 400, $validation['errors']);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->sendError('Validation error: ' . $e->getMessage(), 500);
            return false;
        }
    }

    /**
     * Validate API request with enhanced validation
     */
    protected function validateApiRequest($data, $rules, $messages = [])
    {
        return $this->validateEnhanced($data, $rules, $messages);
    }
    
    /**
     * Check if user is logged in
     */
    protected function requireAuth()
    {
        if (!$this->auth->isLoggedIn()) {
            $this->sendError('Authentication required', 401);
        }
        
        return $this->auth->getCurrentUser();
    }
    
    /**
     * Check if user has specific role
     */
    protected function requireRole($roleId)
    {
        $user = $this->requireAuth();
        
        if ($user['VaiTroID'] < $roleId) {
            $this->sendError('Insufficient privileges', 403);
        }
        
        return $user;
    }
    
    /**
     * Get JSON input
     */
    protected function getJsonInput()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Invalid JSON input', 400);
        }
        
        return $input ?: [];
    }
    
    /**
     * Sanitize input
     */
    protected function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get pagination parameters
     */
    protected function getPaginationParams($defaultLimit = 20, $maxLimit = 100)
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min($maxLimit, max(1, (int)($_GET['limit'] ?? $defaultLimit)));
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Log API error
     */
    protected function logError($message, $context = [])
    {
        $logData = [
            'message' => $message,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user_id' => $this->auth->isLoggedIn() ? $this->auth->getCurrentUser()['ID'] : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        error_log("API Error: " . json_encode($logData, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Handle database errors
     */
    protected function handleDatabaseError(Exception $e, $operation = 'database operation')
    {
        $this->logError("Database error during {$operation}: " . $e->getMessage(), [
            'operation' => $operation,
            'trace' => $e->getTraceAsString()
        ]);
        
        $this->sendError("Failed to perform {$operation}", 500);
    }
    
    /**
     * Main handler method - to be implemented by child classes
     */
    abstract public function handle();
}