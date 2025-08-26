<?php

namespace Tro365\Core;

use Exception;
use Tro365\Helpers\ModernValidationHelper;
use Tro365\Helpers\LoggerHelper;

/**
 * Config Class
 * Tro365 - Website thuê trọ
 */
class Config extends BaseModel
{
    protected $table = 'CauHinh';
    private static $cache = [];
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Hook: Before creating config
     */
    protected function beforeCreate(&$data)
    {
        // Validate config data using Symfony Validator
        $validation = \Tro365\Helpers\ValidationHelper::validateValue($data['TenCH'] ?? '', [
            new \Symfony\Component\Validator\Constraints\NotBlank(['message' => 'Tên cấu hình không được để trống']),
            new \Symfony\Component\Validator\Constraints\Length([
                'max' => 100,
                'maxMessage' => 'Tên cấu hình không được quá {{ limit }} ký tự'
            ])
        ]);

        if (!$validation['valid']) {
            LoggerHelper::error('Config validation failed', ['errors' => $validation['errors']]);
            throw new Exception(implode(', ', $validation['errors']));
        }
        
        // Check if config name exists
        if ($this->nameExists($data['TenCH'])) {
            throw new Exception("Tên cấu hình đã tồn tại");
        }
    }
    
    /**
     * Hook: Before updating config
     */
    protected function beforeUpdate($id, &$data)
    {
        // Remove sensitive fields
        unset($data['ID'], $data['NgayCapNhat']);
        
        if (empty($data)) {
            throw new Exception("Không có dữ liệu để cập nhật");
        }
        
        // Clear cache after update
        self::$cache = [];
    }
    
    /**
     * Check if config name exists
     */
    public function nameExists($name)
    {
        $sql = "SELECT ID FROM CauHinh WHERE TenCH = :name";
        $result = $this->db->selectOne($sql, ['name' => $name]);
        return !empty($result);
    }
    
    /**
     * Get config by name
     */
    public function getByName($name)
    {
        $sql = "SELECT * FROM CauHinh WHERE TenCH = :name";
        return $this->db->selectOne($sql, ['name' => $name]);
    }
    
    /**
     * Get config value by name
     */
    public function getValue($name, $default = null)
    {
        // Check cache first
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        
        $config = $this->getByName($name);
        $value = $config ? $config['GiaTri'] : $default;
        
        // Cache the value
        self::$cache[$name] = $value;
        
        return $value;
    }
    
    /**
     * Set config value
     */
    public function setValue($name, $value, $description = null)
    {
        $existing = $this->getByName($name);
        
        if ($existing) {
            // Update existing config
            $data = ['GiaTri' => $value];
            if ($description !== null) {
                $data['MoTa'] = $description;
            }
            $result = $this->update($existing['ID'], $data);
        } else {
            // Create new config
            $data = [
                'TenCH' => $name,
                'GiaTri' => $value,
                'MoTa' => $description
            ];
            $result = $this->create($data);
        }
        
        // Clear cache
        self::$cache = [];
        
        return $result;
    }
    
    /**
     * Get all configs as key-value array
     */
    public function getAllAsArray()
    {
        $sql = "SELECT TenCH, GiaTri FROM CauHinh";
        $configs = $this->db->select($sql);
        
        $result = [];
        foreach ($configs as $config) {
            $result[$config['TenCH']] = $config['GiaTri'];
        }
        
        return $result;
    }
    
    /**
     * Get system settings
     */
    public function getSystemSettings()
    {
        return [
            'ty_le_hoa_hong' => (float)$this->getValue('ty_le_hoa_hong', 5.0),
            'so_bai_dang_moi_trang' => (int)$this->getValue('so_bai_dang_moi_trang', 20),
            'thoi_gian_hieu_luc_bai_dang' => (int)$this->getValue('thoi_gian_hieu_luc_bai_dang', 30),
            'email_admin' => $this->getValue('email_admin', 'admin@tro365.com'),
            'sdt_hotline' => $this->getValue('sdt_hotline', '1900xxxx'),
            'ten_website' => $this->getValue('ten_website', 'Trọ 365'),
            'mo_ta_website' => $this->getValue('mo_ta_website', 'Website thuê trọ uy tín số 1 Việt Nam'),
            'dia_chi_cong_ty' => $this->getValue('dia_chi_cong_ty', 'Hà Nội, Việt Nam'),
            'email_lien_he' => $this->getValue('email_lien_he', 'contact@tro365.com'),
            'facebook_url' => $this->getValue('facebook_url', 'https://facebook.com/tro365'),
            'zalo_url' => $this->getValue('zalo_url', 'https://zalo.me/tro365')
        ];
    }
    
    /**
     * Update system settings
     */
    public function updateSystemSettings($settings)
    {
        foreach ($settings as $key => $value) {
            $this->setValue($key, $value);
        }
        
        return true;
    }
    
    /**
     * Clear cache
     */
    public static function clearCache()
    {
        self::$cache = [];
    }
    
    /**
     * Build WHERE clause for filters
     */
    protected function buildWhereClause($filters, &$where, &$params)
    {
        if (!empty($filters['search'])) {
            $where .= " AND (TenCH LIKE :search OR MoTa LIKE :search2)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }
    }
}
