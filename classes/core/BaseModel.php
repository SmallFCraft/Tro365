<?php

namespace Tro365\Core;

use Exception;
use Tro365\Helpers\ValidationHelper;

/**
 * Base Model Class
 * Tro365 - Website thuê trọ
 * 
 * Provides common CRUD operations and validation logic
 */
abstract class BaseModel
{
    protected $db;
    protected $table;
    protected $primaryKey = 'ID';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    

    
    /**
     * Generic create method
     */
    public function create($data)
    {
        try {
            $this->beforeCreate($data);
            $result = $this->db->insert($this->table, $data);
            $this->afterCreate($result, $data);
            return $result;
        } catch (Exception $e) {
            throw new Exception("Lỗi tạo dữ liệu: " . $e->getMessage());
        }
    }
    
    /**
     * Generic get by ID method
     */
    public function getById($id)
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Generic update method
     */
    public function update($id, $data)
    {
        try {
            $this->beforeUpdate($id, $data);
            $result = $this->db->update(
                $this->table, 
                $data, 
                "{$this->primaryKey} = :id", 
                ['id' => $id]
            );
            $this->afterUpdate($id, $data);
            return $result;
        } catch (Exception $e) {
            throw new Exception("Lỗi cập nhật dữ liệu: " . $e->getMessage());
        }
    }
    
    /**
     * Generic delete method
     */
    public function delete($id)
    {
        try {
            $this->beforeDelete($id);
            $result = $this->db->delete(
                $this->table, 
                "{$this->primaryKey} = :id", 
                ['id' => $id]
            );
            $this->afterDelete($id);
            return $result;
        } catch (Exception $e) {
            throw new Exception("Lỗi xóa dữ liệu: " . $e->getMessage());
        }
    }
    
    /**
     * Generic count method
     */
    public function count($filters = [])
    {
        $where = "1=1";
        $params = [];
        
        $this->buildWhereClause($filters, $where, $params);
        
        return $this->db->count($this->table, $where, $params);
    }
    
    /**
     * Generic getAll method with pagination
     */
    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        $where = "1=1";
        $params = [];
        
        $this->buildWhereClause($filters, $where, $params);
        
        $offset = ($page - 1) * $limit;
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $sql = $this->buildSelectQuery($where) . " LIMIT :limit OFFSET :offset";
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Build WHERE clause for filters
     */
    protected function buildWhereClause($filters, &$where, &$params)
    {
        // Override in child classes for specific filtering logic
    }
    
    /**
     * Build SELECT query
     */
    protected function buildSelectQuery($where)
    {
        return "SELECT * FROM {$this->table} WHERE {$where} ORDER BY {$this->primaryKey} DESC";
    }
    
    // Hook methods - override in child classes
    protected function beforeCreate(&$data) {}
    protected function afterCreate($result, $data) {}
    protected function beforeUpdate($id, &$data) {}
    protected function afterUpdate($id, $data) {}
    protected function beforeDelete($id) {}
    protected function afterDelete($id) {}
}
