<?php
/**
 * AJAX endpoint for rejecting sellers
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;
use Tro365\Core\Database;
use Tro365\Activity;

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    exit('Access denied');
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$auth = new Auth();
$db = Database::getInstance();

// Require admin/moderator role
try {
    $auth->requireModerator();
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$currentUser = $auth->getCurrentUser();

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dữ liệu không hợp lệ');
    }
    
    $sellerId = (int)($input['seller_id'] ?? 0);
    $reason = trim($input['reason'] ?? '');
    $csrfToken = $input['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verify_csrf_token($csrfToken)) {
        throw new Exception('Token bảo mật không hợp lệ');
    }
    
    if (!$sellerId) {
        throw new Exception('ID seller không hợp lệ');
    }
    
    if (empty($reason)) {
        throw new Exception('Vui lòng nhập lý do từ chối');
    }
    
    if (strlen($reason) > 500) {
        throw new Exception('Lý do từ chối không được quá 500 ký tự');
    }
    
    // Check if seller exists and is pending
    $seller = $db->selectOne("SELECT * FROM DangKySeller WHERE ID = :id AND TrangThai = 0", ['id' => $sellerId]);
    
    if (!$seller) {
        throw new Exception('Không tìm thấy seller chờ duyệt');
    }
    
    // Update seller status to rejected
    $db->update('DangKySeller', [
        'TrangThai' => 2,
        'NguoiDuyet' => $currentUser['ID'],
        'NgayDuyet' => date('Y-m-d H:i:s'),
        'LyDoTuChoi' => $reason
    ], 'ID = :id', ['id' => $sellerId]);
    
    // Log activity
    try {
        $activity = new Activity();
        $activity->log($currentUser['ID'], 'reject_seller', 'Từ chối đăng ký seller: ' . $seller['HoTenChuTro'], [
            'seller_id' => $sellerId,
            'customer_id' => $seller['KhachHangID'],
            'reason' => $reason
        ]);
    } catch (Exception $e) {
        writeLog("Activity log error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Từ chối đăng ký seller thành công!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
