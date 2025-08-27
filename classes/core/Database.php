<?php

namespace Tro365\Core;

use PDO;
use PDOException;
use Exception;

/**
 * Database Class
 * Tro365 - Website thuê trọ
 */
class Database
{
    private $connection;
    private static $instance = null;
    
    public function __construct()
    {
        require_once __DIR__ . '/../../config/database/connection.php';
        $this->connection = \DatabaseConnection::getInstance()->getConnection();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Execute a SELECT query
     */
    public function select($sql, $params = [])
    {
        $startTime = microtime(true);
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->logQuery($sql, $params, microtime(true) - $startTime);
            return $result;
        } catch (PDOException $e) {
            $this->logQuery($sql, $params, microtime(true) - $startTime, $e->getMessage());
            $this->logError($e, $sql, $params);
            throw new Exception("Lỗi truy vấn dữ liệu");
        }
    }
    
    /**
     * Execute a SELECT query and return single row
     */
    public function selectOne($sql, $params = [])
    {
        $startTime = microtime(true);
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->logQuery($sql, $params, microtime(true) - $startTime);
            return $result;
        } catch (PDOException $e) {
            $this->logQuery($sql, $params, microtime(true) - $startTime, $e->getMessage());
            $this->logError($e, $sql, $params);
            throw new Exception("Lỗi truy vấn dữ liệu");
        }
    }
    
    /**
     * Execute an INSERT query
     */
    public function insert($table, $data)
    {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($data);
            
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            $this->logError($e, $sql ?? '', $data);
            throw new Exception("Lỗi thêm dữ liệu");
        }
    }
    
    /**
     * Execute an UPDATE query
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        try {
            $setClause = [];
            foreach (array_keys($data) as $key) {
                $setClause[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $setClause);
            
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            $stmt = $this->connection->prepare($sql);
            
            // Merge data and where parameters
            $params = array_merge($data, $whereParams);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError($e, $sql ?? '', $params ?? []);
            throw new Exception("Lỗi cập nhật dữ liệu");
        }
    }
    
    /**
     * Execute a DELETE query
     */
    public function delete($table, $where, $params = [])
    {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            throw new Exception("Lỗi xóa dữ liệu");
        }
    }
    
    /**
     * Execute a custom query
     */
    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            throw new Exception("Lỗi thực thi truy vấn");
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->connection->rollback();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Count records
     */
    public function count($table, $where = '1=1', $params = [])
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$table} WHERE {$where}";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            return 0;
        }
    }
    
    /**
     * Check if record exists
     */
    public function exists($table, $where, $params = [])
    {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Log database queries for debug
     */
    private function logQuery($sql, $params = [], $executionTime = 0, $error = null)
    {
        // Prevent infinite loops - don't log queries from debug/logging system
        if (strpos($sql, 'CauHinh') !== false || strpos($sql, 'debug') !== false) {
            return;
        }

        // Only log if debug mode is enabled
        if (!function_exists('isDebugModeEnabled')) {
            return;
        }

        try {
            // Check debug mode using the helper function
            if (function_exists('isDebugModeEnabled') && isDebugModeEnabled()) {
                // Add to Debug Manager if available
                if (class_exists('\Tro365\Services\DebugManager')) {
                    $debugManager = \Tro365\Services\DebugManager::getInstance();
                    $debugManager->addQuery($sql, $params, round($executionTime * 1000, 2)); // Convert to ms
                }
            }
        } catch (Exception $e) {
            // Ignore logging errors to prevent infinite loops
        }
    }

    /**
     * Log database errors
     */
    private function logError($exception, $sql, $params)
    {
        $error = [
            'message' => $exception->getMessage(),
            'sql' => $sql,
            'params' => $params,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        error_log("Database Error: " . json_encode($error));

        // Add to Debug Manager if available
        if (class_exists('\Tro365\Services\DebugManager') && function_exists('isDebugModeEnabled') && isDebugModeEnabled()) {
            try {
                $debugManager = \Tro365\Services\DebugManager::getInstance();
                $debugManager->addError($exception->getMessage(), $exception->getFile(), $exception->getLine(), [
                    'sql' => $sql,
                    'params' => $params
                ]);
            } catch (Exception $e) {
                // Ignore debug errors
            }
        }
    }
}
