<?php
/**
 * Contact Management Class
 * Tro365 - Website thuê trọ
 */

namespace Tro365;

use Exception;
use Tro365\Core\BaseModel;
use Tro365\Helpers\ModernValidationHelper;
use Tro365\Helpers\LoggerHelper;
use Symfony\Component\Validator\Constraints as Assert;
use Tro365\Helpers\StatusHelper;

class Contact extends BaseModel
{
    protected $table = 'LienHe';

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Hook: Before creating contact
     */
    protected function beforeCreate(&$data)
    {
        // Validate contact form data using Symfony Validator
        $validation = \Tro365\Helpers\ValidationHelper::validateContactForm([
            'name' => $data['HoTen'] ?? '',
            'email' => $data['Email'] ?? '',
            'subject' => 'Liên hệ về bài đăng #' . ($data['BaiDangID'] ?? ''),
            'message' => $data['GhiChu'] ?? 'Tôi quan tâm đến bài đăng này.'
        ]);

        // Also validate phone number separately
        $phoneValidation = \Tro365\Helpers\ValidationHelper::validateValue($data['SDT'] ?? '', [
            new Assert\NotBlank(['message' => 'Số điện thoại không được để trống']),
            new Assert\Regex([
                'pattern' => '/^(84|0)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-6|8|9]|9[0-4|6-9])[0-9]{7}$/',
                'message' => 'Số điện thoại không hợp lệ'
            ])
        ]);

        $allErrors = array_merge($validation['errors'], $phoneValidation['errors']);

        if (!empty($allErrors)) {
            LoggerHelper::error('Contact creation validation failed', [
                'errors' => $allErrors,
                'post_id' => $data['BaiDangID'] ?? 'unknown'
            ]);
            throw new Exception(implode(', ', $allErrors));
        }

        // Check if contact already exists
        $existing = $this->db->selectOne(
            "SELECT ID FROM LienHe WHERE BaiDangID = :post_id AND NguoiLienHeID = :user_id AND TrangThai != :cancelled",
            [
                'post_id' => $data['BaiDangID'],
                'user_id' => $data['NguoiLienHeID'],
                'cancelled' => StatusHelper::CONTACT_CANCELLED
            ]
        );

        if ($existing) {
            throw new Exception('Bạn đã liên hệ về bài đăng này rồi');
        }

        // Set default status
        $data['TrangThai'] = StatusHelper::CONTACT_PENDING;
    }

    /**
     * Hook: After creating contact
     */
    protected function afterCreate($contactId, $data)
    {
        // Log activity
        try {
            $activity = new Activity();
            $activity->log(
                $data['NguoiLienHeID'],
                'contact_post',
                'Liên hệ về bài đăng: ' . ($data['PostTitle'] ?? 'Bài đăng #' . $data['BaiDangID']),
                ['contact_id' => $contactId, 'post_id' => $data['BaiDangID']]
            );
        } catch (Exception $e) {
            // Silent fail for activity logging
        }
    }
    
    /**
     * Get contact by ID with related data
     */
    public function getById($id)
    {
        $sql = "SELECT lh.*,
                       bd.TieuDe as TenBaiDang,
                       bd.Gia as GiaBaiDang,
                       nguoi_lien_he.HoTen as TenNguoiLienHe,
                       nguoi_lien_he.Email as EmailNguoiLienHe,
                       chu_nha.HoTen as TenChuNha,
                       chu_nha.Email as EmailChuNha
                FROM LienHe lh
                JOIN BaiDang bd ON lh.BaiDangID = bd.ID
                JOIN KhachHang nguoi_lien_he ON lh.NguoiLienHeID = nguoi_lien_he.ID
                JOIN KhachHang chu_nha ON lh.ChuNhaID = chu_nha.ID
                WHERE lh.ID = :id";

        return $this->db->selectOne($sql, ['id' => $id]);
    }
    
    /**
     * Get contacts for a user
     */
    public function getByUser($userId, $type = 'sent', $page = 1, $limit = 10)
    {
        try {
            $offset = ($page - 1) * $limit;
            
            if ($type === 'sent') {
                // Contacts sent by user
                $sql = "SELECT lh.*, 
                               bd.TieuDe as TenBaiDang,
                               bd.Gia as GiaBaiDang,
                               chu_nha.HoTen as TenChuNha,
                               chu_nha.SDT as SDTChuNha
                        FROM LienHe lh
                        JOIN BaiDang bd ON lh.BaiDangID = bd.ID
                        JOIN KhachHang chu_nha ON lh.ChuNhaID = chu_nha.ID
                        WHERE lh.NguoiLienHeID = :user_id
                        ORDER BY lh.NgayTao DESC
                        LIMIT :limit OFFSET :offset";
            } else {
                // Contacts received by user (landlord)
                $sql = "SELECT lh.*, 
                               bd.TieuDe as TenBaiDang,
                               bd.Gia as GiaBaiDang,
                               nguoi_lien_he.HoTen as TenNguoiLienHe,
                               nguoi_lien_he.Email as EmailNguoiLienHe
                        FROM LienHe lh
                        JOIN BaiDang bd ON lh.BaiDangID = bd.ID
                        JOIN KhachHang nguoi_lien_he ON lh.NguoiLienHeID = nguoi_lien_he.ID
                        WHERE lh.ChuNhaID = :user_id
                        ORDER BY lh.NgayTao DESC
                        LIMIT :limit OFFSET :offset";
            }
            
            return $this->db->select($sql, [
                'user_id' => $userId,
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (Exception $e) {
            writeLog("Get user contacts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update contact status
     */
    public function updateStatus($id, $status, $userId = null)
    {
        try {
            // Validate status
            $validStatuses = [
                self::STATUS_PENDING,
                self::STATUS_CONTACTED,
                self::STATUS_INTERESTED,
                self::STATUS_DEAL,
                self::STATUS_CANCELLED
            ];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Trạng thái không hợp lệ');
            }
            
            // Check permission if userId provided
            if ($userId) {
                $contact = $this->getById($id);
                if (!$contact || ($contact['ChuNhaID'] != $userId && $contact['NguoiLienHeID'] != $userId)) {
                    throw new Exception('Bạn không có quyền cập nhật liên hệ này');
                }
            }
            
            $result = $this->db->update(
                'LienHe',
                ['TrangThai' => $status],
                'ID = :id',
                ['id' => $id]
            );
            
            // Log activity
            if ($userId) {
                try {
                    $activity = new Activity();
                    $activity->log(
                        $userId,
                        'update_contact',
                        'Cập nhật trạng thái liên hệ: ' . $status,
                        ['contact_id' => $id, 'status' => $status]
                    );
                } catch (Exception $e) {
                    // Silent fail for activity logging
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get contact statistics
     */
    public function getStats($userId = null, $dateFrom = null, $dateTo = null)
    {
        try {
            $conditions = [];
            $params = [];
            
            if ($userId) {
                $conditions[] = "(NguoiLienHeID = :user_id OR ChuNhaID = :user_id)";
                $params['user_id'] = $userId;
            }
            
            if ($dateFrom) {
                $conditions[] = "NgayTao >= :date_from";
                $params['date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $conditions[] = "NgayTao <= :date_to";
                $params['date_to'] = $dateTo;
            }
            
            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN TrangThai = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN TrangThai = 'contacted' THEN 1 ELSE 0 END) as contacted,
                        SUM(CASE WHEN TrangThai = 'interested' THEN 1 ELSE 0 END) as interested,
                        SUM(CASE WHEN TrangThai = 'deal' THEN 1 ELSE 0 END) as deal,
                        SUM(CASE WHEN TrangThai = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM LienHe $whereClause";
            
            return $this->db->selectOne($sql, $params);
        } catch (Exception $e) {
            writeLog("Get contact stats error: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'contacted' => 0,
                'interested' => 0,
                'deal' => 0,
                'cancelled' => 0
            ];
        }
    }
    
    /**
     * Get all contacts (admin view)
     */
    public function getAll($page = 1, $limit = 20, $filters = [])
    {
        try {
            $offset = ($page - 1) * $limit;
            $conditions = [];
            $params = [];
            
            if (!empty($filters['status'])) {
                $conditions[] = "lh.TrangThai = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['post_id'])) {
                $conditions[] = "lh.BaiDangID = :post_id";
                $params['post_id'] = $filters['post_id'];
            }
            
            if (!empty($filters['search'])) {
                $conditions[] = "(lh.HoTen LIKE :search OR lh.SDT LIKE :search OR lh.Email LIKE :search)";
                $params['search'] = "%{$filters['search']}%";
            }
            
            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $sql = "SELECT lh.*, 
                           bd.TieuDe as TenBaiDang,
                           bd.Gia as GiaBaiDang,
                           nguoi_lien_he.HoTen as TenNguoiLienHe,
                           chu_nha.HoTen as TenChuNha
                    FROM LienHe lh
                    JOIN BaiDang bd ON lh.BaiDangID = bd.ID
                    JOIN KhachHang nguoi_lien_he ON lh.NguoiLienHeID = nguoi_lien_he.ID
                    JOIN KhachHang chu_nha ON lh.ChuNhaID = chu_nha.ID
                    $whereClause
                    ORDER BY lh.NgayTao DESC
                    LIMIT :limit OFFSET :offset";
            
            $params['limit'] = $limit;
            $params['offset'] = $offset;
            
            return $this->db->select($sql, $params);
        } catch (Exception $e) {
            writeLog("Get all contacts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count contacts
     */
    public function count($filters = [])
    {
        try {
            $conditions = [];
            $params = [];

            if (!empty($filters['status'])) {
                $conditions[] = "TrangThai = :status";
                $params['status'] = $filters['status'];
            }

            if (!empty($filters['post_id'])) {
                $conditions[] = "BaiDangID = :post_id";
                $params['post_id'] = $filters['post_id'];
            }

            if (!empty($filters['landlord_id'])) {
                $conditions[] = "ChuNhaID = :landlord_id";
                $params['landlord_id'] = $filters['landlord_id'];
            }

            if (!empty($filters['tenant_id'])) {
                $conditions[] = "NguoiLienHeID = :tenant_id";
                $params['tenant_id'] = $filters['tenant_id'];
            }

            if (!empty($filters['search'])) {
                $conditions[] = "(HoTen LIKE :search OR SDT LIKE :search OR Email LIKE :search)";
                $params['search'] = "%{$filters['search']}%";
            }

            $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
            $sql = "SELECT COUNT(*) as count FROM LienHe $whereClause";

            $result = $this->db->selectOne($sql, $params);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            writeLog("Count contacts error: " . $e->getMessage());
            return 0;
        }
    }
}
