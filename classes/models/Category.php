<?php

namespace Tro365\Models;

use Tro365\Core\BaseModel;
use Tro365\Helpers\ValidationHelper;

/**
 * Category Class
 * Tro365 - Website thuê trọ
 */
class Category extends BaseModel
{
    protected $table = 'DanhMuc';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Hook: Before creating category
     */
    protected function beforeCreate(&$data)
    {
        // Validate required fields
        ValidationHelper::validateRequired($data, ['TenDM']);
        
        // Validate name length
        if (strlen($data['TenDM']) > 100) {
            throw new Exception("Tên danh mục không được quá 100 ký tự");
        }
        
        // Check if category name exists
        if ($this->nameExists($data['TenDM'])) {
            throw new Exception("Tên danh mục đã tồn tại");
        }
        
        // Set default values
        $data['ThuTu'] = $data['ThuTu'] ?? 0;
        $data['TrangThai'] = $data['TrangThai'] ?? 1;
    }
    
    /**
     * Hook: Before updating category
     */
    protected function beforeUpdate($id, &$data)
    {
        // Remove sensitive fields
        unset($data['ID'], $data['NgayTao']);
        
        if (empty($data)) {
            throw new Exception("Không có dữ liệu để cập nhật");
        }
        
        // Validate name if provided
        if (isset($data['TenDM'])) {
            if (strlen($data['TenDM']) > 100) {
                throw new Exception("Tên danh mục không được quá 100 ký tự");
            }
            
            // Check if name exists for other categories
            $existing = $this->getByName($data['TenDM']);
            if ($existing && $existing['ID'] != $id) {
                throw new Exception("Tên danh mục đã tồn tại");
            }
        }
    }
    
    /**
     * Check if category name exists
     */
    public function nameExists($name)
    {
        $sql = "SELECT ID FROM DanhMuc WHERE TenDM = :name";
        $result = $this->db->selectOne($sql, ['name' => $name]);
        return !empty($result);
    }
    
    /**
     * Get category by name
     */
    public function getByName($name)
    {
        $sql = "SELECT * FROM DanhMuc WHERE TenDM = :name";
        return $this->db->selectOne($sql, ['name' => $name]);
    }
    
    /**
     * Get all active categories ordered by ThuTu
     */
    public function getAllActive()
    {
        $sql = "SELECT * FROM DanhMuc WHERE TrangThai = 1 ORDER BY ThuTu ASC, TenDM ASC";
        return $this->db->select($sql);
    }
    
    /**
     * Get categories with post count (only active categories)
     */
    public function getCategoriesWithPostCount()
    {
        $sql = "SELECT dm.*, COUNT(bd.ID) as SoBaiDang
                FROM DanhMuc dm
                LEFT JOIN BaiDang bd ON dm.ID = bd.DanhMucID AND bd.TrangThai = 1
                WHERE dm.TrangThai = 1
                GROUP BY dm.ID
                ORDER BY dm.ThuTu ASC, dm.TenDM ASC";

        return $this->db->select($sql);
    }

    /**
     * Get all categories with post count (for admin)
     */
    public function getAllCategoriesWithPostCount()
    {
        $sql = "SELECT dm.*, COUNT(bd.ID) as SoBaiDang
                FROM DanhMuc dm
                LEFT JOIN BaiDang bd ON dm.ID = bd.DanhMucID AND bd.TrangThai = 1
                GROUP BY dm.ID
                ORDER BY dm.ThuTu ASC, dm.TenDM ASC";

        return $this->db->select($sql);
    }
    
    /**
     * Update category order
     */
    public function updateOrder($id, $order)
    {
        return $this->update($id, ['ThuTu' => $order]);
    }
    
    /**
     * Toggle category status
     */
    public function toggleStatus($id)
    {
        $category = $this->getById($id);
        if (!$category) {
            throw new Exception("Không tìm thấy danh mục");
        }
        
        $newStatus = $category['TrangThai'] == 1 ? 0 : 1;
        return $this->update($id, ['TrangThai' => $newStatus]);
    }
    
    /**
     * Build WHERE clause for filters
     */
    protected function buildWhereClause($filters, &$where, &$params)
    {
        if (!empty($filters['search'])) {
            $where .= " AND TenDM LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['status'])) {
            $where .= " AND TrangThai = :status";
            $params['status'] = $filters['status'];
        }
    }
}
