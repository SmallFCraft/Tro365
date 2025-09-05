<?php
/**
 * Settings AJAX Handler
 * Tro365 - Website thuê trọ
 * 
 * Xử lý tất cả AJAX requests cho trang admin settings
 */

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../includes/functions/auth.php';

// Chỉ cho phép AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    exit('Bad Request');
}

// Chỉ cho phép POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

header('Content-Type: application/json');

try {
    // Simple auth check first
    require_once __DIR__ . '/../../../includes/functions/auth.php';
    $auth = new \Tro365\Core\Auth();
    if (!$auth->isLoggedIn() || !$auth->hasRole(ROLE_ADMIN)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Debug log
    error_log("Settings AJAX Handler - Action: " . $action);
    
    switch ($action) {
        case 'update_version':
            $version = trim($_POST['app_version'] ?? '');
            $description = trim($_POST['version_description'] ?? '');

            // Validate version format
            if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Định dạng phiên bản không hợp lệ. Sử dụng định dạng x.y.z (ví dụ: 1.2.3)'
                ]);
                break;
            }

            // Update version using global function
            $result = setAppVersion($version, $description);

            if ($result) {
                $versionHistory = getVersionHistory();
                echo json_encode([
                    'success' => true,
                    'message' => 'Phiên bản đã được cập nhật thành công',
                    'version' => $version,
                    'history' => $versionHistory
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Không thể cập nhật phiên bản'
                ]);
            }
            break;
            
        case 'edit_version_description':
            $version = trim($_POST['version'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($version) || empty($description)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Vui lòng nhập đầy đủ thông tin'
                ]);
                break;
            }

            // Get and update version history directly
            $config = new \Tro365\Core\Config();
            $historyJson = $config->getValue('version_history', '[]');
            $history = json_decode($historyJson, true) ?: [];

            // If history is empty, initialize it first
            if (empty($history)) {
                $currentVersion = getAppVersion();
                $history = [
                    [
                        'version' => $currentVersion,
                        'date' => date('Y-m-d H:i:s'),
                        'previous_version' => null,
                        'description' => 'Phiên bản hiện tại',
                        'is_custom_description' => false
                    ]
                ];
            }

            // Find and update the specified version entry
            $updated = false;
            foreach ($history as &$entry) {
                if ($entry['version'] === $version) {
                    $entry['description'] = $description;
                    $entry['is_custom_description'] = true;
                    $updated = true;
                    break;
                }
            }

            if (!$updated) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Phiên bản "' . $version . '" không tồn tại trong lịch sử'
                ]);
                break;
            }

            // Save updated history
            $result = $config->setValue('version_history', json_encode($history));

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Mô tả phiên bản đã được cập nhật thành công',
                    'version' => $version,
                    'description' => $description,
                    'history' => $history
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Không thể lưu thay đổi vào database'
                ]);
            }
            break;
            
        case 'test_email':
            $testEmail = trim($_POST['test_email'] ?? '');

            // Validate email if provided
            if (!empty($testEmail) && !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Địa chỉ email test không hợp lệ!'
                ]);
                break;
            }

            // Load EmailService
            require_once __DIR__ . '/../../../classes/services/EmailService.php';
            $emailService = new \Tro365\Services\EmailService();

            // Send test email
            $result = $emailService->sendTestEmail($testEmail);

            if ($result) {
                $configInfo = $emailService->getConfigInfo();
                $message = 'Email test đã được gửi thành công!';

                if ($configInfo['driver'] === 'smtp') {
                    $message .= ' (SMTP: ' . $configInfo['host'] . ':' . $configInfo['port'] . ')';
                } else {
                    $message .= ' (Driver: ' . $configInfo['driver'] . ')';
                }

                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'config_info' => [
                        'driver' => $configInfo['driver'],
                        'host' => $configInfo['host'],
                        'port' => $configInfo['port'],
                        'encryption' => $configInfo['encryption'],
                        'phpmailer_available' => $configInfo['phpmailer_available']
                    ]
                ]);
            } else {
                $errors = $emailService->getErrors();
                echo json_encode([
                    'success' => false,
                    'message' => 'Không thể gửi email test: ' . implode(', ', $errors)
                ]);
            }
            break;
            
        case 'test_smtp_connection':
            $smtpConfig = [
                'driver' => 'smtp',
                'host' => trim($_POST['mail_host'] ?? ''),
                'port' => (int)($_POST['mail_port'] ?? 587),
                'encryption' => trim($_POST['mail_encryption'] ?? 'tls'),
                'username' => trim($_POST['mail_username'] ?? ''),
                'password' => trim($_POST['mail_password'] ?? ''),
                'from_address' => trim($_POST['mail_from_address'] ?? ''),
                'from_name' => trim($_POST['mail_from_name'] ?? '')
            ];

            // Load EmailService
            require_once __DIR__ . '/../../../classes/services/EmailService.php';
            $emailService = new \Tro365\Services\EmailService();

            // Test SMTP connection
            $result = $emailService->testConnection($smtpConfig);
            echo json_encode($result);
            break;
            
        case 'save_tinymce':
            $apiKey = trim($_POST['tinymce_api_key'] ?? '');

            if (empty($apiKey)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'TinyMCE API Key không được để trống!'
                ]);
                break;
            }

            // Save to database
            $config = new \Tro365\Core\Config();
            $config->setValue('tinymce_api_key', $apiKey, 'TinyMCE API Key cho Rich Text Editor');

            echo json_encode([
                'success' => true,
                'message' => 'Cấu hình TinyMCE đã được lưu thành công!'
            ]);
            break;
            
        case 'save_room_limit':
            $maxRooms = intval($_POST['max_rooms_per_post'] ?? 50);

            // Validate room limit
            if ($maxRooms < 1 || $maxRooms > 1000) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Số phòng tối đa phải từ 1 đến 1000!'
                ]);
                break;
            }

            // Save to database
            $config = new \Tro365\Core\Config();
            $config->setValue('max_rooms_per_post', $maxRooms, 'Số phòng tối đa có thể đăng trong một bài đăng');

            echo json_encode([
                'success' => true,
                'message' => 'Cấu hình số phòng đã được lưu thành công!',
                'max_rooms' => $maxRooms
            ]);
            break;
            
        case 'auto_save':
            // Simple auto save without controller
            $config = new \Tro365\Core\Config();

            // Handle auto save for different settings sections
            if (isset($_POST['website_settings'])) {
                $websiteSettings = [
                    'ten_website' => trim($_POST['ten_website'] ?? ''),
                    'mo_ta_website' => trim($_POST['mo_ta_website'] ?? ''),
                    'dia_chi_cong_ty' => trim($_POST['dia_chi_cong_ty'] ?? ''),
                    'email_admin' => trim($_POST['email_admin'] ?? ''),
                    'email_lien_he' => trim($_POST['email_lien_he'] ?? ''),
                    'sdt_hotline' => trim($_POST['sdt_hotline'] ?? ''),
                    'facebook_url' => trim($_POST['facebook_url'] ?? ''),
                    'zalo_url' => trim($_POST['zalo_url'] ?? '')
                ];

                foreach ($websiteSettings as $key => $value) {
                    $config->setValue($key, $value);
                }
            }

            if (isset($_POST['system_settings'])) {
                $systemSettings = [
                    'ty_le_hoa_hong' => (float)($_POST['ty_le_hoa_hong'] ?? 5.0),
                    'so_bai_dang_moi_trang' => (int)($_POST['so_bai_dang_moi_trang'] ?? 20),
                    'thoi_gian_hieu_luc_bai_dang' => (int)($_POST['thoi_gian_hieu_luc_bai_dang'] ?? 30),
                    'max_upload_size' => (int)($_POST['max_upload_size'] ?? 5),
                    'allowed_file_types' => trim($_POST['allowed_file_types'] ?? ''),
                    'enable_registration' => isset($_POST['enable_registration']) ? 1 : 0,
                    'enable_seller_registration' => isset($_POST['enable_seller_registration']) ? 1 : 0,
                    'require_email_verification' => isset($_POST['require_email_verification']) ? 1 : 0,
                    'enable_maintenance_mode' => isset($_POST['enable_maintenance_mode']) ? 1 : 0,
                    'max_rooms_per_post' => (int)($_POST['max_rooms_per_post'] ?? 50)
                ];

                foreach ($systemSettings as $key => $value) {
                    $config->setValue($key, $value);
                }
            }

            if (isset($_POST['email_settings'])) {
                $emailSettings = [
                    'mail_driver' => trim($_POST['mail_driver'] ?? 'smtp'),
                    'mail_host' => trim($_POST['mail_host'] ?? ''),
                    'mail_port' => (int)($_POST['mail_port'] ?? 587),
                    'mail_username' => trim($_POST['mail_username'] ?? ''),
                    'mail_password' => trim($_POST['mail_password'] ?? ''),
                    'mail_encryption' => trim($_POST['mail_encryption'] ?? 'tls'),
                    'mail_from_address' => trim($_POST['mail_from_address'] ?? ''),
                    'mail_from_name' => trim($_POST['mail_from_name'] ?? '')
                ];

                foreach ($emailSettings as $key => $value) {
                    $config->setValue($key, $value);
                }
            }

            if (isset($_POST['seo_settings'])) {
                $seoSettings = [
                    'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
                    'meta_description' => trim($_POST['meta_description'] ?? ''),
                    'google_analytics_id' => trim($_POST['google_analytics_id'] ?? ''),
                    'google_search_console' => trim($_POST['google_search_console'] ?? ''),
                    'facebook_pixel_id' => trim($_POST['facebook_pixel_id'] ?? ''),
                    'enable_sitemap' => isset($_POST['enable_sitemap']) ? 1 : 0,
                    'enable_robots_txt' => isset($_POST['enable_robots_txt']) ? 1 : 0
                ];

                foreach ($seoSettings as $key => $value) {
                    $config->setValue($key, $value);
                }
            }

            // Handle individual settings that are not in sections
            $individualSettings = [
                'app_debug' => isset($_POST['app_debug']) ? 1 : 0,
                'tinymce_api_key' => trim($_POST['tinymce_api_key'] ?? '')
            ];

            foreach ($individualSettings as $key => $value) {
                if (isset($_POST[$key]) || $key === 'app_debug') { // Always process app_debug for checkbox
                    $config->setValue($key, $value);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đã lưu tự động'
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action không hợp lệ'
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi PHP: ' . $e->getMessage()
    ]);
}
