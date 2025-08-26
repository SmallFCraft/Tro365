<?php
/**
 * Settings Controller
 * Tro365 - Website thuê trọ
 * 
 * Quản lý business logic cho trang admin settings
 */

namespace Tro365;

require_once __DIR__ . '/../config/app.php';

class SettingsController
{
    private $config;
    private $db;
    private $auth;

    public function __construct()
    {
        $this->config = new \Tro365\Core\Config();
        $this->db = new \Tro365\Core\Database();
        $this->auth = new \Tro365\Core\Auth();
    }

    /**
     * Kiểm tra quyền admin
     */
    public function checkAdminAccess()
    {
        if (!$this->auth->isLoggedIn() || !$this->auth->hasRole(ROLE_ADMIN)) {
            http_response_code(403);
            include __DIR__ . '/../pages/errors/403.php';
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

        $validation = \Tro365\Helpers\ValidationHelper::enhancedValidate($formData, [
            'website_name' => 'required|min:3|max:100',
            'website_description' => 'nullable|max:500',
            'company_address' => 'nullable|max:200',
            'admin_email' => 'nullable|email',
            'contact_email' => 'nullable|email',
            'hotline' => 'nullable|regex:/^(84|0[3|5|7|8|9])+([0-9]{8})$/',
            'facebook_url' => 'nullable|url',
            'zalo_url' => 'nullable|url'
        ], [
            'website_name.required' => 'Vui lòng nhập tên website',
            'website_name.min' => 'Tên website phải có ít nhất 3 ký tự',
            'website_name.max' => 'Tên website không được vượt quá 100 ký tự',
            'website_description.max' => 'Mô tả website không được vượt quá 500 ký tự',
            'company_address.max' => 'Địa chỉ công ty không được vượt quá 200 ký tự',
            'admin_email.email' => 'Email admin không hợp lệ',
            'contact_email.email' => 'Email liên hệ không hợp lệ',
            'hotline.regex' => 'Số hotline không hợp lệ',
            'facebook_url.url' => 'URL Facebook không hợp lệ',
            'zalo_url.url' => 'URL Zalo không hợp lệ'
        ]);

        if (!$validation['valid']) {
            $errors = [];
            foreach ($validation['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            throw new \Exception(implode(', ', $errors));
        }

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
            throw new \Exception('Định dạng phiên bản không hợp lệ. Sử dụng định dạng x.y.z (ví dụ: 1.2.3)');
        }

        return setAppVersion($version, $description);
    }

    /**
     * Cập nhật mô tả version
     */
    public function updateVersionDescription($version, $description)
    {
        if (empty($version) || empty($description)) {
            throw new \Exception('Vui lòng nhập đầy đủ thông tin');
        }

        return updateAnyVersionDescription($version, $description);
    }

    /**
     * Test email configuration
     */
    public function testEmail($testEmail = '')
    {
        require_once __DIR__ . '/EmailService.php';
        
        // Validate email if provided
        if (!empty($testEmail) && !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Địa chỉ email test không hợp lệ!');
        }

        $emailService = new EmailService();
        $result = $emailService->sendTestEmail($testEmail);

        if (!$result) {
            $errors = $emailService->getErrors();
            throw new \Exception('Không thể gửi email test: ' . implode(', ', $errors));
        }

        return [
            'success' => true,
            'config_info' => $emailService->getConfigInfo()
        ];
    }

    /**
     * Test SMTP connection
     */
    public function testSmtpConnection($smtpConfig)
    {
        require_once __DIR__ . '/EmailService.php';
        
        $emailService = new EmailService();
        return $emailService->testConnection($smtpConfig);
    }

    /**
     * Lưu TinyMCE API key
     */
    public function saveTinyMceApiKey($apiKey)
    {
        if (empty($apiKey)) {
            throw new \Exception('TinyMCE API Key không được để trống!');
        }

        $this->config->setValue('tinymce_api_key', $apiKey);
        return true;
    }

    /**
     * Lưu cấu hình số phòng
     */
    public function saveRoomLimit($maxRooms)
    {
        $maxRooms = intval($maxRooms);
        
        if ($maxRooms < 1 || $maxRooms > 1000) {
            throw new \Exception('Số phòng tối đa phải từ 1 đến 1000!');
        }

        $this->config->setValue('max_rooms_per_post', $maxRooms);
        return $maxRooms;
    }

    /**
     * Lấy thống kê số phòng
     */
    public function getRoomStatistics()
    {
        $totalPosts = $this->db->count('BaiDang', '1=1');
        $avgRooms = $this->db->selectOne("SELECT AVG(SoPhong) as avg_rooms FROM BaiDang WHERE SoPhong > 0")['avg_rooms'] ?? 0;
        $maxRooms = $this->db->selectOne("SELECT MAX(SoPhong) as max_rooms FROM BaiDang")['max_rooms'] ?? 0;
        $currentLimit = $this->config->getValue('max_rooms_per_post', '50');

        return [
            'total_posts' => $totalPosts,
            'avg_rooms' => $avgRooms,
            'max_rooms' => $maxRooms,
            'current_limit' => $currentLimit
        ];
    }

    /**
     * Auto save settings
     */
    public function autoSave($data)
    {
        try {
            if (isset($data['website_settings'])) {
                $this->updateWebsiteSettings($data);
            }
            
            if (isset($data['system_settings'])) {
                $this->updateSystemSettings($data);
            }
            
            if (isset($data['email_settings'])) {
                $this->updateEmailSettings($data);
            }
            
            if (isset($data['seo_settings'])) {
                $this->updateSeoSettings($data);
            }
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Lỗi auto-save: ' . $e->getMessage());
        }
    }
}
