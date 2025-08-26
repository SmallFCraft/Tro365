<?php
/**
 * API Debug Middleware
 * Tro365 - Website thuê trọ
 * 
 * Middleware to capture and log API requests/responses for debugging
 */

class APIDebugMiddleware
{
    private static $startTime;
    private static $requestData;
    
    public static function start()
    {
        if (!isDebugModeEnabled()) {
            return;
        }
        
        self::$startTime = microtime(true);
        
        // Capture request data
        self::$requestData = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'headers' => getallheaders() ?: [],
            'query' => $_GET,
            'body' => self::getRequestBody(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Log API request start
        logAPI("API Request started: {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}", [
            'request_data' => self::$requestData
        ]);
    }
    
    public static function end($responseData = null, $statusCode = 200)
    {
        if (!isDebugModeEnabled() || !self::$startTime) {
            return;
        }
        
        $executionTime = microtime(true) - self::$startTime;
        
        // Add to Debug Manager
        if (class_exists('\Tro365\DebugManager')) {
            $debugManager = \Tro365\DebugManager::getInstance();
            $debugManager->addAPICall(
                self::$requestData['method'],
                self::$requestData['uri'],
                self::$requestData,
                $responseData,
                $statusCode,
                round($executionTime * 1000, 2) // Convert to ms
            );
        }
        
        // Log API response
        logAPI("API Request completed: {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}", [
            'status_code' => $statusCode,
            'execution_time' => round($executionTime * 1000, 2) . 'ms',
            'response_size' => strlen(json_encode($responseData)),
            'memory_usage' => formatBytes(memory_get_usage(true))
        ]);
    }
    
    private static function getRequestBody()
    {
        $body = file_get_contents('php://input');
        
        // Try to decode JSON
        if (!empty($body)) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        // Return raw body if not JSON
        return $body;
    }
    
    public static function logError($error, $context = [])
    {
        if (!isDebugModeEnabled()) {
            return;
        }
        
        // Add to Debug Manager
        if (class_exists('\Tro365\DebugManager')) {
            $debugManager = \Tro365\DebugManager::getInstance();
            $debugManager->addError($error, '', 0, array_merge($context, [
                'api_request' => self::$requestData
            ]));
        }
        
        // Log error
        logAPI("API Error: {$error}", array_merge($context, [
            'request_data' => self::$requestData
        ]));
    }
}

/**
 * Helper function to wrap API responses with debug info
 */
function debugAPIResponse($data, $statusCode = 200, $message = 'Success')
{
    // End API debug tracking
    APIDebugMiddleware::end($data, $statusCode);
    
    // Add debug headers if debug mode is enabled
    if (isDebugModeEnabled()) {
        header('X-Debug-Mode: enabled');
        header('X-Debug-Memory: ' . formatBytes(memory_get_usage(true)));
        header('X-Debug-Time: ' . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms');
        
        if (class_exists('\Tro365\DebugManager')) {
            $debugManager = \Tro365\DebugManager::getInstance();
            $metrics = $debugManager->getPerformanceMetrics();
            header('X-Debug-Queries: ' . $metrics['queries_count']);
            header('X-Debug-Errors: ' . $metrics['errors_count']);
        }
    }
    
    // Return response
    return [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'status_code' => $statusCode,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c'),
        'debug' => isDebugModeEnabled() ? [
            'memory_usage' => formatBytes(memory_get_usage(true)),
            'execution_time' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms',
            'queries_count' => class_exists('\Tro365\DebugManager') ? 
                \Tro365\DebugManager::getInstance()->getPerformanceMetrics()['queries_count'] : 0
        ] : null
    ];
}

/**
 * Helper function to handle API errors with debug info
 */
function debugAPIError($error, $statusCode = 500, $context = [])
{
    // Log error
    APIDebugMiddleware::logError($error, $context);
    
    // End API debug tracking
    APIDebugMiddleware::end(['error' => $error], $statusCode);
    
    // Set error headers
    http_response_code($statusCode);
    
    if (isDebugModeEnabled()) {
        header('X-Debug-Mode: enabled');
        header('X-Debug-Error: ' . base64_encode($error));
    }
    
    // Return error response
    return [
        'success' => false,
        'status_code' => $statusCode,
        'message' => $error,
        'data' => null,
        'timestamp' => date('c'),
        'debug' => isDebugModeEnabled() ? [
            'context' => $context,
            'memory_usage' => formatBytes(memory_get_usage(true)),
            'execution_time' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms'
        ] : null
    ];
}
?>
