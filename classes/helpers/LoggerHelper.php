<?php
/**
 * Logger Helper using Monolog
 * Tro365 - Website thuê trọ
 */

namespace Tro365\Helpers;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

class LoggerHelper
{
    private static ?Logger $logger = null;
    private static array $channels = [];

    /**
     * Get default logger instance
     */
    public static function getLogger(string $channel = 'app'): Logger
    {
        if (!isset(self::$channels[$channel])) {
            self::$channels[$channel] = self::createLogger($channel);
        }
        
        return self::$channels[$channel];
    }

    /**
     * Create logger instance
     */
    private static function createLogger(string $channel): Logger
    {
        $logger = new Logger($channel);

        // Create logs directory if not exists
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Simple file handler without rotation
        $fileHandler = new StreamHandler(
            $logDir . '/' . $channel . '.log',
            Level::Debug
        );

        // Consistent formatter with timestamps (Y-m-d H:i:s) and context
        $formatter = new LineFormatter(
            "[%datetime%] [%level_name%] %message% %context%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        $fileHandler->setFormatter($formatter);
        $logger->pushHandler($fileHandler);

        // Add console handler for development
        if (APP_DEBUG) {
            $consoleHandler = new StreamHandler('php://stdout', Level::Debug);
            $consoleHandler->setFormatter($formatter);
            $logger->pushHandler($consoleHandler);
        }

        return $logger;
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = [], string $channel = 'app'): void
    {
        self::getLogger($channel)->info($message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $message, array $context = [], string $channel = 'app'): void
    {
        self::getLogger($channel)->error($message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = [], string $channel = 'app'): void
    {
        self::getLogger($channel)->warning($message, $context);
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = [], string $channel = 'app'): void
    {
        self::getLogger($channel)->debug($message, $context);
    }

    /**
     * Log authentication events
     */
    public static function logAuth(string $action, array $context = []): void
    {
        self::info("Auth: {$action}", $context, 'auth');
    }

    /**
     * Log API requests
     */
    public static function logAPI(string $endpoint, string $method, array $context = []): void
    {
        self::info("API: {$method} {$endpoint}", $context, 'api');
    }

    /**
     * Log database queries (for debugging)
     */
    public static function logQuery(string $query, array $params = [], float $executionTime = 0): void
    {
        if (APP_DEBUG) {
            self::debug("Query executed", [
                'query' => $query,
                'params' => $params,
                'execution_time' => $executionTime . 'ms'
            ], 'database');
        }
    }

    /**
     * Log user actions
     */
    public static function logUserAction(int $userId, string $action, array $context = []): void
    {
        self::info("User action: {$action}", array_merge([
            'user_id' => $userId
        ], $context), 'user');
    }

    /**
     * Log system errors
     */
    public static function logSystemError(\Throwable $exception, array $context = []): void
    {
        self::error("System error: " . $exception->getMessage(), array_merge([
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ], $context), 'system');
    }
}
