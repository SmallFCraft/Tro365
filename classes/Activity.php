<?php
/**
 * Activity Management Class
 * Tro365 - Website thuê trọ
 */

namespace Tro365;

use Tro365\Core\Database;

class Activity
{
    private $db;
    
    // Activity types
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_REGISTER = 'register';
    const TYPE_CREATE_POST = 'create_post';
    const TYPE_EDIT_POST = 'edit_post';
    const TYPE_DELETE_POST = 'delete_post';
    const TYPE_APPROVE_POST = 'approve_post';
    const TYPE_REJECT_POST = 'reject_post';
    const TYPE_UPDATE_PROFILE = 'update_profile';
    const TYPE_CHANGE_PASSWORD = 'change_password';
    const TYPE_UPLOAD_FILE = 'upload_file';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
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
                'DuLieu' => $data ? json_encode($data) : null
            ];
            
            return $this->db->insert('HoatDong', $activityData);
        } catch (Exception $e) {
            writeLog("Activity log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user activities
     */
    public function getUserActivities($userId, $limit = 10, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM HoatDong 
                    WHERE KhachHangID = :user_id 
                    ORDER BY NgayTao DESC 
                    LIMIT :limit OFFSET :offset";
            
            $params = [
                'user_id' => $userId,
                'limit' => $limit,
                'offset' => $offset
            ];
            
            return $this->db->select($sql, $params);
        } catch (Exception $e) {
            writeLog("Get user activities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent activities for all users (admin view)
     */
    public function getRecentActivities($limit = 20)
    {
        try {
            $sql = "SELECT h.*, k.HoTen, k.TenDN 
                    FROM HoatDong h
                    JOIN KhachHang k ON h.KhachHangID = k.ID
                    ORDER BY h.NgayTao DESC 
                    LIMIT :limit";
            
            return $this->db->select($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            writeLog("Get recent activities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity count by type
     */
    public function getActivityCount($userId = null, $type = null, $dateFrom = null, $dateTo = null)
    {
        try {
            $conditions = [];
            $params = [];
            
            if ($userId) {
                $conditions[] = "KhachHangID = :user_id";
                $params['user_id'] = $userId;
            }
            
            if ($type) {
                $conditions[] = "LoaiHoatDong = :type";
                $params['type'] = $type;
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
            $sql = "SELECT COUNT(*) as count FROM HoatDong $whereClause";
            
            $result = $this->db->selectOne($sql, $params);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            writeLog("Get activity count error: " . $e->getMessage());
            return 0;
        }
    }
    
    // cleanOldActivities method removed as it was unused
    
    /**
     * Format activity description for display
     */
    public function formatActivity($activity)
    {
        $icon = $this->getActivityIcon($activity['LoaiHoatDong']);
        $timeAgo = timeAgo($activity['NgayTao']);
        
        return [
            'icon' => $icon,
            'description' => $activity['MoTa'],
            'time' => $timeAgo,
            'type' => $activity['LoaiHoatDong'],
            'data' => $activity['DuLieu'] ? json_decode($activity['DuLieu'], true) : null
        ];
    }
    
    /**
     * Get icon for activity type
     */
    private function getActivityIcon($type)
    {
        $icons = [
            self::TYPE_LOGIN => 'fas fa-sign-in-alt text-success',
            self::TYPE_LOGOUT => 'fas fa-sign-out-alt text-muted',
            self::TYPE_REGISTER => 'fas fa-user-plus text-primary',
            self::TYPE_CREATE_POST => 'fas fa-plus-circle text-success',
            self::TYPE_EDIT_POST => 'fas fa-edit text-warning',
            self::TYPE_DELETE_POST => 'fas fa-trash text-danger',
            self::TYPE_APPROVE_POST => 'fas fa-check-circle text-success',
            self::TYPE_REJECT_POST => 'fas fa-times-circle text-danger',
            self::TYPE_UPDATE_PROFILE => 'fas fa-user-edit text-info',
            self::TYPE_CHANGE_PASSWORD => 'fas fa-key text-warning',
            self::TYPE_UPLOAD_FILE => 'fas fa-upload text-info',

            // Admin activities
            'admin_approve_post' => 'fas fa-check-circle text-success',
            'admin_reject_post' => 'fas fa-times-circle text-danger',
            'admin_approve_seller' => 'fas fa-user-check text-success',
            'admin_reject_seller' => 'fas fa-user-times text-danger',
            'admin_reset_seller_status' => 'fas fa-undo text-warning',
            'create_user' => 'fas fa-user-plus text-primary',
            'create_category' => 'fas fa-folder-plus text-success',
            'update_category' => 'fas fa-folder-edit text-warning',
            'delete_category' => 'fas fa-folder-minus text-danger',
            'toggle_category_status' => 'fas fa-toggle-on text-info',

            // Client activities
            'contact_sent' => 'fas fa-envelope text-info',
            'profile_updated' => 'fas fa-user-edit text-info',
            'post_created' => 'fas fa-plus-circle text-success',
            'post_updated' => 'fas fa-edit text-warning',

            // Seller activities
            'seller_register' => 'fas fa-store text-primary',
            'seller_post_created' => 'fas fa-home text-success',
            'seller_post_updated' => 'fas fa-home text-warning'
        ];
        
        return $icons[$type] ?? 'fas fa-circle text-muted';
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
