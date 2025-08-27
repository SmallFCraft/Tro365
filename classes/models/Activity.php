<?php

namespace Tro365\Models;

use Exception;
use Tro365\Core\BaseModel;
use Tro365\Core\Database;

/**
 * Activity Model - Quản lý hoạt động người dùng
 * Tro365 - Website thuê trọ
 */
class Activity extends BaseModel
{
    // Activity types
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_REGISTER = 'register';
    const TYPE_CREATE_POST = 'create_post';
    const TYPE_EDIT_POST = 'edit_post';
    const TYPE_DELETE_POST = 'delete_post';
    const TYPE_UPDATE_PROFILE = 'update_profile';
    const TYPE_CHANGE_PASSWORD = 'change_password';
    const TYPE_SELLER_REGISTER = 'seller_register';
    const TYPE_SELLER_APPROVED = 'seller_approved';
    const TYPE_SELLER_REJECTED = 'seller_rejected';

    protected $table = 'HoatDong';
    protected $primaryKey = 'ID';

    /**
     * Log an activity
     */
    public function log($userId, $type, $description, $data = null)
    {
        try {
            $activityData = [
                'KhachHangID' => $userId,
                'LoaiHoatDong' => $type,
                'MoTa' => $description,
                'DuLieu' => $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null
            ];

            return $this->db->insert($this->table, $activityData);
        } catch (Exception $e) {
            // Log error but don't throw to prevent breaking main functionality
            error_log("Activity logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get activities by user ID
     */
    public function getByUserId($userId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} WHERE KhachHangID = :user_id ORDER BY NgayTao DESC LIMIT :limit OFFSET :offset";
        return $this->db->select($sql, [
            'user_id' => $userId,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Get activities by type
     */
    public function getByType($type, $limit = 50, $offset = 0)
    {
        $sql = "SELECT h.*, k.HoTen, k.Email FROM {$this->table} h
                LEFT JOIN KhachHang k ON h.KhachHangID = k.ID
                WHERE h.LoaiHoatDong = :type
                ORDER BY h.NgayTao DESC
                LIMIT :limit OFFSET :offset";
        return $this->db->select($sql, [
            'type' => $type,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Get recent activities
     */
    public function getRecent($limit = 20)
    {
        $sql = "SELECT h.*, k.HoTen, k.Email FROM {$this->table} h
                LEFT JOIN KhachHang k ON h.KhachHangID = k.ID
                ORDER BY h.NgayTao DESC
                LIMIT :limit";
        return $this->db->select($sql, ['limit' => $limit]);
    }

    /**
     * Count activities by user
     */
    public function countByUser($userId)
    {
        return $this->db->count($this->table, 'KhachHangID = :user_id', ['user_id' => $userId]);
    }

    /**
     * Count activities by type
     */
    public function countByType($type)
    {
        return $this->db->count($this->table, 'LoaiHoatDong = :type', ['type' => $type]);
    }

    /**
     * Delete old activities (cleanup)
     */
    public function deleteOldActivities($days = 90)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->db->delete($this->table, 'NgayTao < :cutoff', ['cutoff' => $cutoffDate]);
    }

    /**
     * Get activity statistics
     */
    // Get latest user activities (joined with user info if needed)
    public function getUserActivities($userId, $limit = 5, $offset = 0)
    {
        $sql = "SELECT h.* FROM {$this->table} h
                WHERE h.KhachHangID = :user_id
                ORDER BY h.NgayTao DESC
                LIMIT :limit OFFSET :offset";
        return $this->db->select($sql, [
            'user_id' => $userId,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Format raw activity row to UI-friendly structure
     */
    public function formatActivity(array $row): array
    {
        $type = $row['LoaiHoatDong'] ?? 'default';
        $description = $row['MoTa'] ?? '';
        $timeStr = '';
        if (!empty($row['NgayTao'])) {
            $ts = strtotime($row['NgayTao']);
            $timeStr = $ts ? date('d/m/Y H:i', $ts) : '';
        }
        return [
            'type' => $type,
            'description' => $description,
            'time' => $timeStr,
        ];
    }

    public function getStats($startDate = null, $endDate = null)
    {
        $whereClause = '1=1';
        $params = [];

        if ($startDate) {
            $whereClause .= ' AND NgayTao >= :start_date';
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $whereClause .= ' AND NgayTao <= :end_date';
            $params['end_date'] = $endDate;
        }

        $sql = "SELECT LoaiHoatDong, COUNT(*) as count FROM {$this->table}
                WHERE {$whereClause}
                GROUP BY LoaiHoatDong
                ORDER BY count DESC";

        return $this->db->select($sql, $params);
    }

    /**
     * Log login activity
     */
    public function logLogin($userId, $ip = null, $userAgent = null)
    {
        $description = "Đăng nhập vào hệ thống";
        $data = [
            'ip' => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'user_agent' => $userAgent ?: ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        ];

        return $this->log($userId, self::TYPE_LOGIN, $description, $data);
    }

    /**
     * Log logout activity
     */
    public function logLogout($userId)
    {
        $description = "Đăng xuất khỏi hệ thống";
        return $this->log($userId, self::TYPE_LOGOUT, $description);
    }

    /**
     * Log post creation
     */
    public function logCreatePost($userId, $postId, $postTitle)
    {
        $description = "Tạo bài đăng mới: " . $postTitle;
        $data = ['post_id' => $postId, 'post_title' => $postTitle];

        return $this->log($userId, self::TYPE_CREATE_POST, $description, $data);
    }

    /**
     * Log post edit
     */
    public function logEditPost($userId, $postId, $postTitle)
    {
        $description = "Chỉnh sửa bài đăng: " . $postTitle;
        $data = ['post_id' => $postId, 'post_title' => $postTitle];

        return $this->log($userId, self::TYPE_EDIT_POST, $description, $data);
    }

    /**
     * Log profile update
     */
    public function logUpdateProfile($userId, $changes = [])
    {
        $description = "Cập nhật thông tin cá nhân";
        $data = ['changes' => $changes];

        return $this->log($userId, self::TYPE_UPDATE_PROFILE, $description, $data);
    }

    /**
     * Log password change
     */
    public function logPasswordChange($userId)
    {
        $description = "Đổi mật khẩu tài khoản";
        $data = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        return $this->log($userId, self::TYPE_CHANGE_PASSWORD, $description, $data);
    }
}

