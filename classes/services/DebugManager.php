<?php
/**
 * Debug Manager Service
 * Tro365 - Website thuê trọ
 * 
 * Central debug controller for comprehensive debugging
 */

namespace Tro365\Services;

class DebugManager
{
    private static $instance = null;
    private $debugData = [];
    private $startTime;
    private $startMemory;
    private $queries = [];
    private $errors = [];
    private $apiCalls = [];
    private $enabled = false;

    private function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->enabled = $this->isDebugEnabled();
        
        if ($this->enabled) {
            $this->initializeDebug();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function isDebugEnabled()
    {
        try {
            // Use the same logic as isDebugModeEnabled() helper function
            return function_exists('isDebugModeEnabled') ? isDebugModeEnabled() : false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function initializeDebug()
    {
        // Set error handler to capture errors
        set_error_handler([$this, 'errorHandler']);
        
        // Set exception handler
        set_exception_handler([$this, 'exceptionHandler']);
        
        // Register shutdown function
        register_shutdown_function([$this, 'shutdownHandler']);
        
        logDebug('Debug Manager initialized', [
            'memory_start' => formatBytes($this->startMemory),
            'time_start' => $this->startTime
        ]);
    }

    public function addDebugInfo($category, $key, $value)
    {
        if (!$this->enabled) return;
        
        if (!isset($this->debugData[$category])) {
            $this->debugData[$category] = [];
        }
        
        $this->debugData[$category][$key] = $value;
    }

    public function addQuery($sql, $params = [], $executionTime = 0)
    {
        if (!$this->enabled) return;
        
        $this->queries[] = [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
        
        logDatabase("SQL Query executed", [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime . 'ms'
        ]);
    }

    public function addError($message, $file = '', $line = 0, $context = [])
    {
        $error = [
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'context' => $context,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
        ];
        
        $this->errors[] = $error;
        
        logError("Error captured: {$message}", [
            'file' => $file,
            'line' => $line,
            'context' => $context
        ]);
    }

    public function addAPICall($method, $url, $requestData = [], $responseData = [], $statusCode = 200, $executionTime = 0)
    {
        if (!$this->enabled) return;
        
        $this->apiCalls[] = [
            'method' => $method,
            'url' => $url,
            'request' => $requestData,
            'response' => $responseData,
            'status_code' => $statusCode,
            'execution_time' => $executionTime,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true)
        ];
        
        logAPI("API Call: {$method} {$url}", [
            'status_code' => $statusCode,
            'execution_time' => $executionTime . 'ms',
            'request_size' => strlen(json_encode($requestData)),
            'response_size' => strlen(json_encode($responseData))
        ]);
    }

    public function getPerformanceMetrics()
    {
        if (!$this->enabled) return [];
        
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        return [
            'execution_time' => round(($currentTime - $this->startTime) * 1000, 2), // ms
            'memory_usage' => formatBytes($currentMemory),
            'memory_peak' => formatBytes($peakMemory),
            'memory_start' => formatBytes($this->startMemory),
            'memory_diff' => formatBytes($currentMemory - $this->startMemory),
            'queries_count' => count($this->queries),
            'errors_count' => count($this->errors),
            'api_calls_count' => count($this->apiCalls)
        ];
    }

    public function getAllDebugData()
    {
        if (!$this->enabled) return [];
        
        return [
            'performance' => $this->getPerformanceMetrics(),
            'debug_info' => $this->debugData,
            'queries' => $this->queries,
            'errors' => $this->errors,
            'api_calls' => $this->apiCalls,
            'server_info' => $this->getServerInfo(),
            'request_info' => $this->getRequestInfo()
        ];
    }

    private function getServerInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? '',
            'request_time' => $_SERVER['REQUEST_TIME'] ?? time()
        ];
    }

    private function getRequestInfo()
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
            'request_time' => $_SERVER['REQUEST_TIME'] ?? time()
        ];
    }

    public function errorHandler($severity, $message, $file, $line)
    {
        $this->addError($message, $file, $line, ['severity' => $severity]);
        return false; // Let PHP handle the error normally
    }

    public function exceptionHandler($exception)
    {
        $this->addError(
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            ['exception_class' => get_class($exception)]
        );
    }

    public function shutdownHandler()
    {
        if (!$this->enabled) return;
        
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->addError($error['message'], $error['file'], $error['line'], ['type' => 'fatal']);
        }
        
        // Log final performance metrics
        $metrics = $this->getPerformanceMetrics();
        logPerformance('Request completed', $metrics);
    }

    public function renderDebugPanel()
    {
        if (!$this->enabled) return '';
        
        $debugData = $this->getAllDebugData();
        
        ob_start();
        include dirname(__DIR__, 2) . '/includes/debug/debug-panel.php';
        return ob_get_clean();
    }
}
