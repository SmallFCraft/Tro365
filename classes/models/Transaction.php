<?php
/**
 * Transaction Management Class
 * Tro365 - Website thuê trọ
 */

namespace Tro365\Models;

use Tro365\Core\BaseModel;
use Tro365\Helpers\ValidationHelper;
use Tro365\Helpers\StatusHelper;

class Transaction extends BaseModel
{
    protected $table = 'GiaoDich';

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Hook: Before creating transaction
     */
    protected function beforeCreate(&$data)
    {
        // Validate required fields
        ValidationHelper::validateRequired($data, ['LienHeID', 'BaiDangID', 'NguoiThueID', 'ChuNhaID', 'GiaThue']);

        // Validate price
        ValidationHelper::validatePrice($data['GiaThue']);

        if (isset($data['TienCoc'])) {
            ValidationHelper::validatePrice($data['TienCoc']);
        }

        // Validate contact exists and is in deal status
        $contact = $this->db->selectOne(
            "SELECT * FROM LienHe WHERE ID = :id AND TrangThai = :status",
            ['id' => $data['LienHeID'], 'status' => StatusHelper::CONTACT_DEAL]
        );

        if (!$contact) {
            throw new Exception('Liên hệ không tồn tại hoặc chưa được xác nhận thành công');
        }

        // Check if transaction already exists for this contact
        $existing = $this->db->selectOne(
            "SELECT ID FROM GiaoDich WHERE LienHeID = :contact_id",
            ['contact_id' => $data['LienHeID']]
        );

        if ($existing) {
            throw new Exception('Giao dịch cho liên hệ này đã tồn tại');
        }

        // Set default status
        $data['TrangThai'] = StatusHelper::TRANSACTION_PENDING;
    }

    /**
     * Hook: After creating transaction
     */
    protected function afterCreate($transactionId, $data)
    {
        // Log activity
        try {
            $activity = new Activity();
            $activity->log(
                $data['NguoiThueID'],
                'transaction_created',
                'Tạo giao dịch thuê trọ',
                ['transaction_id' => $transactionId, 'post_id' => $data['BaiDangID']]
            );
        } catch (Exception $e) {
            // Silent fail for activity logging
        }
    }
    
    /**
     * Get transaction by ID
     */
    public function getById($id)
    {
        try {
            $sql = "SELECT gd.*, 
                           lh.HoTen as TenNguoiThue,
                           lh.SDT as SDTNguoiThue,
                           lh.Email as EmailNguoiThue,
                           bd.TieuDe as TenBaiDang,
                           bd.DiaChi as DiaChiBaiDang,
                           nguoi_thue.HoTen as TenKhachThue,
                           chu_nha.HoTen as TenChuNha,
                           chu_nha.Email as EmailChuNha
                    FROM GiaoDich gd
                    JOIN LienHe lh ON gd.LienHeID = lh.ID
                    JOIN BaiDang bd ON gd.BaiDangID = bd.ID
                    JOIN KhachHang nguoi_thue ON gd.NguoiThueID = nguoi_thue.ID
                    JOIN KhachHang chu_nha ON gd.ChuNhaID = chu_nha.ID
                    WHERE gd.ID = :id";
            
            return $this->db->selectOne($sql, ['id' => $id]);
        } catch (Exception $e) {
            writeLog("Get transaction error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get transactions for a user
     */
    public function getByUser($userId, $type = 'all', $page = 1, $limit = 10)
    {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = ['user_id' => $userId];
            
            if ($type === 'tenant') {
                $conditions[] = "gd.NguoiThueID = :user_id";
            } elseif ($type === 'landlord') {
                $conditions[] = "gd.ChuNhaID = :user_id";
            } else {
                $conditions[] = "(gd.NguoiThueID = :user_id OR gd.ChuNhaID = :user_id)";
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
            
            $sql = "SELECT gd.*, 
                           bd.TieuDe as TenBaiDang,
                           bd.DiaChi as DiaChiBaiDang,
                           nguoi_thue.HoTen as TenNguoiThue,
                           chu_nha.HoTen as TenChuNha
                    FROM GiaoDich gd
                    JOIN BaiDang bd ON gd.BaiDangID = bd.ID
                    JOIN KhachHang nguoi_thue ON gd.NguoiThueID = nguoi_thue.ID
                    JOIN KhachHang chu_nha ON gd.ChuNhaID = chu_nha.ID
                    $whereClause
                    ORDER BY gd.NgayTao DESC
                    LIMIT :limit OFFSET :offset";
            
            $params['limit'] = $limit;
            $params['offset'] = $offset;
            
            return $this->db->select($sql, $params);
        } catch (Exception $e) {
            writeLog("Get user transactions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update transaction status
     */
    public function updateStatus($id, $status, $userId = null)
    {
        try {
            // Validate status
            $validStatuses = [
                self::STATUS_PENDING,
                self::STATUS_CONFIRMED,
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED
            ];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Trạng thái không hợp lệ');
            }
            
            // Check permission if userId provided
            if ($userId) {
                $transaction = $this->getById($id);
                if (!$transaction || ($transaction['ChuNhaID'] != $userId && $transaction['NguoiThueID'] != $userId)) {
                    throw new Exception('Bạn không có quyền cập nhật giao dịch này');
                }
            }
            
            $result = $this->db->update(
                'GiaoDich',
                ['TrangThai' => $status],
                'ID = :id',
                ['id' => $id]
            );
            
            // Calculate commission if transaction is completed
            if ($status === self::STATUS_COMPLETED) {
                $this->calculateCommission($id);
            }
            
            return $result;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Calculate and record commission
     */
    private function calculateCommission($transactionId)
    {
        try {
            $transaction = $this->getById($transactionId);
            if (!$transaction) {
                return;
            }

            // Check if commission already exists
            $existingCommission = $this->db->selectOne(
                "SELECT ID FROM HoaHong WHERE GiaoDichID = :transaction_id",
                ['transaction_id' => $transactionId]
            );

            if ($existingCommission) {
                return; // Commission already calculated
            }

            $commissionRate = getCommissionRate() / 100; // Convert percentage to decimal
            $commissionAmount = $transaction['GiaThue'] * $commissionRate;

            // Get admin user (assuming admin has role level 5)
            $admin = $this->db->selectOne(
                "SELECT ID FROM KhachHang WHERE VaiTroID = 5 LIMIT 1"
            );

            if (!$admin) {
                writeLog("No admin found for commission calculation");
                return;
            }

            // Record commission in HoaHong table
            $this->db->insert('HoaHong', [
                'GiaoDichID' => $transactionId,
                'AdminID' => $admin['ID'],
                'SoTien' => $commissionAmount,
                'TrangThai' => 'pending'
            ]);

            // Log commission activity
            try {
                $activity = new Activity();
                $activity->log(
                    $transaction['ChuNhaID'],
                    'commission_calculated',
                    "Tính hoa hồng: " . number_format($commissionAmount, 0, ',', '.') . " VNĐ",
                    ['transaction_id' => $transactionId, 'commission' => $commissionAmount]
                );
            } catch (Exception $e) {
                // Silent fail for activity logging
            }

        } catch (Exception $e) {
            writeLog("Calculate commission error: " . $e->getMessage());
        }
    }
    
    /**
     * Get all transactions (admin view)
     */
    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            if (!empty($filters['status'])) {
                $conditions[] = "gd.TrangThai = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(bd.TieuDe LIKE :search OR nguoi_thue.HoTen LIKE :search OR chu_nha.HoTen LIKE :search)";
                $params['search'] = "%{$filters['search']}%";
            }
            
            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $sql = "SELECT gd.*, 
                           bd.TieuDe as TenBaiDang,
                           nguoi_thue.HoTen as TenNguoiThue,
                           chu_nha.HoTen as TenChuNha
                    FROM GiaoDich gd
                    JOIN BaiDang bd ON gd.BaiDangID = bd.ID
                    JOIN KhachHang nguoi_thue ON gd.NguoiThueID = nguoi_thue.ID
                    JOIN KhachHang chu_nha ON gd.ChuNhaID = chu_nha.ID
                    $whereClause
                    ORDER BY gd.NgayTao DESC
                    LIMIT :limit OFFSET :offset";
            
            $params['limit'] = $limit;
            $params['offset'] = $offset;
            
            return $this->db->select($sql, $params);
        } catch (Exception $e) {
            writeLog("Get all transactions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count transactions
     */
    public function count($filters = [])
    {
        try {
            $conditions = [];
            $params = [];
            
            if (!empty($filters['status'])) {
                $conditions[] = "gd.TrangThai = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['user_id'])) {
                $conditions[] = "(gd.NguoiThueID = :user_id OR gd.ChuNhaID = :user_id)";
                $params['user_id'] = $filters['user_id'];
            }
            
            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $sql = "SELECT COUNT(*) as total FROM GiaoDich gd $whereClause";
            
            $result = $this->db->selectOne($sql, $params);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            writeLog("Count transactions error: " . $e->getMessage());
            return 0;
        }
    }
}
