<?php

namespace Tro365\Controllers;

use Exception;
use Tro365\Core\Config;
use Tro365\Core\Database;
use Tro365\Core\Auth;

/**
 * Settings Controller
 * Tro365 - Website thuê trọ
 *
 * Quản lý business logic cho trang admin settings
 */
class SettingsController
{
    private $config;
    private $db;
    private $auth;

    public function __construct()
    {
        $this->config = new Config();
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }

    /**
     * Kiểm tra quyền admin
     */
    public function checkAdminAccess()
    {
        if (!$this->auth->isLoggedIn() || !$this->auth->hasRole(ROLE_ADMIN)) {
            http_response_code(403);
            include dirname(__DIR__, 2) . '/pages/errors/403.php';
            exit;
        }
    }

    /**
     * Lấy tất cả settings hiện tại
     */
    public function getAllSettings()
    {
        $settings = $this->config->getSystemSettings();

        // Thêm các settings bổ sung
        $additionalSettings = [
            'max_upload_size' => $this->config->getValue('max_upload_size', 5),
            'allowed_file_types' => $this->config->getValue('allowed_file_types', 'jpg,jpeg,png,gif,webp,pdf,doc,docx'),
            'enable_registration' => (bool)$this->config->getValue('enable_registration', 1),
            'enable_seller_registration' => (bool)$this->config->getValue('enable_seller_registration', 1),
            'require_email_verification' => (bool)$this->config->getValue('require_email_verification', 0),
            'enable_maintenance_mode' => (bool)$this->config->getValue('enable_maintenance_mode', 0),
            'app_debug' => (bool)$this->config->getValue('app_debug', 0),

            // Email settings
            'mail_driver' => $this->config->getValue('mail_driver', 'smtp'),
            'mail_host' => $this->config->getValue('mail_host', ''),
            'mail_port' => (int)$this->config->getValue('mail_port', 587),
            'mail_username' => $this->config->getValue('mail_username', ''),
            'mail_password' => $this->config->getValue('mail_password', ''),
            'mail_encryption' => $this->config->getValue('mail_encryption', 'tls'),
            'mail_from_address' => $this->config->getValue('mail_from_address', ''),
            'mail_from_name' => $this->config->getValue('mail_from_name', ''),

            // SEO settings
            'meta_keywords' => $this->config->getValue('meta_keywords', ''),
            'meta_description' => $this->config->getValue('meta_description', ''),
            'google_analytics_id' => $this->config->getValue('google_analytics_id', ''),
            'google_search_console' => $this->config->getValue('google_search_console', ''),
            'facebook_pixel_id' => $this->config->getValue('facebook_pixel_id', ''),
            'enable_sitemap' => (bool)$this->config->getValue('enable_sitemap', 1),
            'enable_robots_txt' => (bool)$this->config->getValue('enable_robots_txt', 1),

            // TinyMCE settings
            'tinymce_api_key' => $this->config->getValue('tinymce_api_key', ''),

            // Room settings
            'max_rooms_per_post' => $this->config->getValue('max_rooms_per_post', '50')
        ];

        return array_merge($settings, $additionalSettings);
    }

    /**
     * Cập nhật website settings
     */
    public function updateWebsiteSettings($data)
    {
        // Enhanced validation using rakit/validation
        $formData = [
            'website_name' => trim($data['ten_website'] ?? ''),
            'website_description' => trim($data['mo_ta_website'] ?? ''),
            'company_address' => trim($data['dia_chi_cong_ty'] ?? ''),
            'admin_email' => trim($data['email_admin'] ?? ''),
            'contact_email' => trim($data['email_lien_he'] ?? ''),
            'hotline' => trim($data['sdt_hotline'] ?? ''),
            'facebook_url' => trim($data['facebook_url'] ?? ''),
            'zalo_url' => trim($data['zalo_url'] ?? '')
        ];

        // Temporarily disable validation to focus on debug panel issue
        // $validation = \Tro365\Helpers\ValidationHelper::enhancedValidate($formData, [
        //     'website_name' => 'required|min:3|max:100',
        //     'website_description' => 'nullable|max:500',
        //     'company_address' => 'nullable|max:200',
        //     'admin_email' => 'nullable|email',
        //     'contact_email' => 'nullable|email',
        //     'hotline' => 'nullable|regex:/^(84[0-9]{9}|0[3|5|7|8|9][0-9]{8})$/',
        //     'facebook_url' => 'nullable|url',
        //     'zalo_url' => 'nullable|url'
        // ], [
        //     'website_name.required' => 'Vui lòng nhập tên website',
        //     'website_name.min' => 'Tên website phải có ít nhất 3 ký tự',
        //     'website_name.max' => 'Tên website không được vượt quá 100 ký tự',
        //     'website_description.max' => 'Mô tả website không được vượt quá 500 ký tự',
        //     'company_address.max' => 'Địa chỉ công ty không được vượt quá 200 ký tự',
        //     'admin_email.email' => 'Email admin không hợp lệ',
        //     'contact_email.email' => 'Email liên hệ không hợp lệ',
        //     'hotline.regex' => 'Số hotline không hợp lệ',
        //     'facebook_url.url' => 'URL Facebook không hợp lệ',
        //     'zalo_url.url' => 'URL Zalo không hợp lệ'
        // ]);

        // if (!$validation['valid']) {
        //     $errors = [];
        //     foreach ($validation['errors'] as $field => $fieldErrors) {
        //         $errors = array_merge($errors, $fieldErrors);
        //     }
        //     throw new Exception(implode(', ', $errors));
        // }

        $websiteSettings = [
            'ten_website' => $formData['website_name'],
            'mo_ta_website' => $formData['website_description'],
            'dia_chi_cong_ty' => $formData['company_address'],
            'email_admin' => $formData['admin_email'],
            'email_lien_he' => $formData['contact_email'],
            'sdt_hotline' => $formData['hotline'],
            'facebook_url' => $formData['facebook_url'],
            'zalo_url' => $formData['zalo_url']
        ];

        foreach ($websiteSettings as $key => $value) {
            $this->config->setValue($key, $value);
        }

        return true;
    }

    /**
     * Cập nhật system settings
     */
    public function updateSystemSettings($data)
    {
        $systemSettings = [
            'ty_le_hoa_hong' => (float)($data['ty_le_hoa_hong'] ?? 5.0),
            'so_bai_dang_moi_trang' => (int)($data['so_bai_dang_moi_trang'] ?? 20),
            'thoi_gian_hieu_luc_bai_dang' => (int)($data['thoi_gian_hieu_luc_bai_dang'] ?? 30),
            'max_upload_size' => (int)($data['max_upload_size'] ?? 5),
            'allowed_file_types' => trim($data['allowed_file_types'] ?? ''),
            'enable_registration' => isset($data['enable_registration']) ? 1 : 0,
            'enable_seller_registration' => isset($data['enable_seller_registration']) ? 1 : 0,
            'require_email_verification' => isset($data['require_email_verification']) ? 1 : 0,
            'enable_maintenance_mode' => isset($data['enable_maintenance_mode']) ? 1 : 0,
            'app_debug' => isset($data['app_debug']) ? 1 : 0
        ];

        foreach ($systemSettings as $key => $value) {
            $this->config->setValue($key, $value);
        }

        return true;
    }

    /**
     * Cập nhật email settings
     */
    public function updateEmailSettings($data)
    {
        $emailSettings = [
            'mail_driver' => trim($data['mail_driver'] ?? 'smtp'),
            'mail_host' => trim($data['mail_host'] ?? ''),
            'mail_port' => (int)($data['mail_port'] ?? 587),
            'mail_username' => trim($data['mail_username'] ?? ''),
            'mail_password' => trim($data['mail_password'] ?? ''),
            'mail_encryption' => trim($data['mail_encryption'] ?? 'tls'),
            'mail_from_address' => trim($data['mail_from_address'] ?? ''),
            'mail_from_name' => trim($data['mail_from_name'] ?? '')
        ];

        foreach ($emailSettings as $key => $value) {
            $this->config->setValue($key, $value);
        }

        return true;
    }

    /**
     * Cập nhật SEO settings
     */
    public function updateSeoSettings($data)
    {
        $seoSettings = [
            'meta_keywords' => trim($data['meta_keywords'] ?? ''),
            'meta_description' => trim($data['meta_description'] ?? ''),
            'google_analytics_id' => trim($data['google_analytics_id'] ?? ''),
            'google_search_console' => trim($data['google_search_console'] ?? ''),
            'facebook_pixel_id' => trim($data['facebook_pixel_id'] ?? ''),
            'enable_sitemap' => isset($data['enable_sitemap']) ? 1 : 0,
            'enable_robots_txt' => isset($data['enable_robots_txt']) ? 1 : 0
        ];

        foreach ($seoSettings as $key => $value) {
            $this->config->setValue($key, $value);
        }

        return true;
    }

    /**
     * Cập nhật version
     */
    public function updateVersion($version, $description = '')
    {
        // Validate version format
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            throw new Exception('Định dạng phiên bản không hợp lệ. Sử dụng định dạng x.y.z (ví dụ: 1.2.3)');
        }

        return setAppVersion($version, $description);
    }

    /**
     * Cập nhật mô tả version
     */
    public function updateVersionDescription($version, $description)
    {
        if (empty($version) || empty($description)) {
            throw new Exception('Vui lòng nhập đầy đủ thông tin');
        }

        return updateVersionDescription($version, $description);
    }

    /**
     * Lấy lịch sử version
     */
    public function getVersionHistory()
    {
        return getVersionHistory();
    }

    /**
     * Xóa version khỏi lịch sử
     */
    public function deleteVersion($version)
    {
        if (empty($version)) {
            throw new Exception('Vui lòng chỉ định phiên bản cần xóa');
        }

        return deleteVersionFromHistory($version);
    }

    /**
     * Test email configuration
     */
    public function testEmailConfig($testEmail)
    {
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email test không hợp lệ');
        }

        // Test email sending functionality
        try {
            $subject = 'Test Email Configuration - Tro365';
            $message = 'Đây là email test để kiểm tra cấu hình email của hệ thống Tro365.';

            // Use your email sending function here
            $result = sendTestEmail($testEmail, $subject, $message);

            if ($result) {
                return ['success' => true, 'message' => 'Email test đã được gửi thành công'];
            } else {
                return ['success' => false, 'message' => 'Không thể gửi email test'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi gửi email: ' . $e->getMessage()];
        }
    }

    /**
     * Get system statistics
     */
    public function getSystemStats()
    {
        try {
            $stats = [];

            // User statistics
            $stats['users'] = [
                'total' => $this->db->count('KhachHang'),
                'active' => $this->db->count('KhachHang', 'TrangThai = 1'),
                'sellers' => $this->db->count('KhachHang', 'VaiTroID = ' . ROLE_SELLER),
                'pending_sellers' => $this->db->count('DangKySeller', 'TrangThai = 0')
            ];

            // Post statistics
            $stats['posts'] = [
                'total' => $this->db->count('BaiDang'),
                'active' => $this->db->count('BaiDang', 'TrangThai = 1'),
                'pending' => $this->db->count('BaiDang', 'TrangThai = 0'),
                'expired' => $this->db->count('BaiDang', 'NgayHetHan < NOW()')
            ];

            // Contact statistics
            $stats['contacts'] = [
                'total' => $this->db->count('LienHe'),
                'pending' => $this->db->count('LienHe', 'TrangThai = 0'),
                'resolved' => $this->db->count('LienHe', 'TrangThai = 2')
            ];

            return $stats;
        } catch (Exception $e) {
            error_log("Error getting system stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear system cache
     */
    public function clearCache()
    {
        try {
            // Clear various cache types
            $cleared = [];

            // Clear file cache if exists
            $cacheDir = dirname(__DIR__, 2) . '/cache';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                $cleared[] = 'File cache';
            }

            // Clear session cache
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
                $cleared[] = 'Session cache';
            }

            // Clear opcode cache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $cleared[] = 'Opcode cache';
            }

            return [
                'success' => true,
                'message' => 'Cache đã được xóa: ' . implode(', ', $cleared)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi xóa cache: ' . $e->getMessage()
            ];
        }
    }
}
