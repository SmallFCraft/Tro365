<?php
/**
 * AJAX endpoint for user management actions
 * Tro365 - Website thuê trọ
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Disable debug panel for AJAX requests
define('DISABLE_DEBUG_PANEL', true);

// Include necessary files
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../includes/functions/helpers.php';

use Tro365\Core\Auth;
use Tro365\Models\User;
use Tro365\Core\Database;
use Tro365\Models\Activity;

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Initialize with error handling
try {
    $auth = new Auth();
    $user = new User();
    $db = Database::getInstance();

    // Force refresh session to get latest role
    $auth->updateSession();

    // Check moderator access (return JSON instead of redirect)
    if (!$auth->hasRole(ROLE_MODERATOR)) {
        http_response_code(403);
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
        ob_end_flush();
        exit;
    }

    $currentUser = $auth->getCurrentUser();
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập: ' . $e->getMessage()]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Fallback to POST data if JSON parsing fails
    if (!$input) {
        $input = $_POST;
    }

    // Validate CSRF token
    if (!verify_csrf_token($input['csrf_token'] ?? '')) {
        throw new Exception('Token bảo mật không hợp lệ');
    }

    $action = $input['action'] ?? '';
    $userId = (int)($input['user_id'] ?? 0);

    if (!$userId) {
        throw new Exception('ID người dùng không hợp lệ');
    }

    // Get user info for logging
    $targetUser = $user->getById($userId);
    if (!$targetUser) {
        throw new Exception('Không tìm thấy người dùng');
    }

    $message = '';
    $updatedData = [];

    switch ($action) {
        case 'activate':
            $user->update($userId, ['TrangThai' => 1]);
            $message = 'Kích hoạt tài khoản thành công!';
            $updatedData = ['status' => 1, 'status_text' => 'Hoạt động', 'status_class' => 'success'];
            break;

        case 'deactivate':
            $user->update($userId, ['TrangThai' => 0]);
            $message = 'Vô hiệu hóa tài khoản thành công!';
            $updatedData = ['status' => 0, 'status_text' => 'Vô hiệu hóa', 'status_class' => 'warning'];
            break;

        case 'promote_seller':
            $user->update($userId, ['VaiTroID' => ROLE_SELLER]);
            $message = 'Nâng cấp thành Seller thành công!';
            $updatedData = ['role' => ROLE_SELLER, 'role_text' => 'Seller', 'role_class' => 'info'];
            break;

        case 'demote_user':
            $user->update($userId, ['VaiTroID' => ROLE_USER]);
            $message = 'Hạ cấp về User thành công!';
            $updatedData = ['role' => ROLE_USER, 'role_text' => 'User', 'role_class' => 'secondary'];
            break;

        case 'promote_moderator':
            $user->update($userId, ['VaiTroID' => ROLE_MODERATOR]);
            $message = 'Nâng cấp thành Moderator thành công!';
            $updatedData = ['role' => ROLE_MODERATOR, 'role_text' => 'Moderator', 'role_class' => 'warning'];
            break;

        case 'promote_admin':
            // Only admin can promote to admin
            $auth->requireAdmin();
            $user->update($userId, ['VaiTroID' => ROLE_ADMIN]);
            $message = 'Nâng cấp thành Admin thành công!';
            $updatedData = ['role' => ROLE_ADMIN, 'role_text' => 'Admin', 'role_class' => 'danger'];
            break;

        case 'verify_email':
            $user->update($userId, ['email_verified_at' => date('Y-m-d H:i:s')]);
            $message = 'Xác thực email thành công!';
            $updatedData = ['email_verified' => true, 'email_status' => 'Đã xác thực', 'email_class' => 'success'];
            break;

        case 'unverify_email':
            $user->update($userId, ['email_verified_at' => null]);
            $message = 'Hủy xác thực email thành công!';
            $updatedData = ['email_verified' => false, 'email_status' => 'Chưa xác thực', 'email_class' => 'warning'];
            break;

        case 'change_role':
            // Only admins can change roles
            $auth->requireAdmin();
            $newRole = (int)($input['new_role'] ?? 0);
            $allowedRoles = [ROLE_USER, ROLE_SELLER, ROLE_SUPPORTER, ROLE_MODERATOR, ROLE_ADMIN];
            if (!in_array($newRole, $allowedRoles, true)) {
                throw new Exception('Vai trò không hợp lệ');
            }
            // Prevent self-demotion to avoid accidental lock-out
            if ($currentUser['ID'] === $userId && $newRole !== ROLE_ADMIN) {
                throw new Exception('Không thể thay đổi vai trò của chính bạn');
            }
            $user->update($userId, ['VaiTroID' => $newRole]);
            $roleText = getRoleName($newRole);
            // Map role to badge class (keep consistent with users/index.php)
            $roleClass = 'secondary';
            switch ($newRole) {
                case ROLE_ADMIN: $roleClass = 'danger'; break;
                case ROLE_MODERATOR: $roleClass = 'warning'; break;
                case ROLE_SELLER: $roleClass = 'info'; break;
                case ROLE_SUPPORTER: $roleClass = 'primary'; break;
                default: $roleClass = 'secondary';
            }
            $message = 'Cập nhật vai trò thành công!';
            $updatedData = ['role' => $newRole, 'role_text' => $roleText, 'role_class' => $roleClass];
            break;

        default:
            throw new Exception('Hành động không hợp lệ');
    }

    // Log activity
    try {
        $activity = new Activity();
        $activity->log($currentUser['ID'], 'user_action', "Thực hiện hành động '{$action}' cho người dùng: " . $targetUser['HoTen'], [
            'action' => $action,
            'target_user_id' => $userId,
            'target_user_name' => $targetUser['HoTen']
        ]);
    } catch (Exception $e) {
        writeLog("Activity log error: " . $e->getMessage());
    }

    // Clean output buffer and return success response
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => $message,
        'user_id' => $userId,
        'action' => $action,
        'updated_data' => $updatedData
    ]);

} catch (Exception $e) {
    // Clean output buffer and return error response
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Clean exit to prevent any additional output
ob_end_flush();
exit;
