<?php
/**
 * AJAX endpoint for approving sellers
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
    $csrfToken = $input['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!verify_csrf_token($csrfToken)) {
        throw new Exception('Token bảo mật không hợp lệ');
    }
    
    if (!$sellerId) {
        throw new Exception('ID seller không hợp lệ');
    }
    
    // Check if seller exists and is pending
    $seller = $db->selectOne("SELECT * FROM DangKySeller WHERE ID = :id AND TrangThai = 0", ['id' => $sellerId]);
    
    if (!$seller) {
        throw new Exception('Không tìm thấy seller chờ duyệt');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Update seller status to approved
        $db->update('DangKySeller', [
            'TrangThai' => 1,
            'NguoiDuyet' => $currentUser['ID'],
            'NgayDuyet' => date('Y-m-d H:i:s')
        ], 'ID = :id', ['id' => $sellerId]);
        
        // Update user role to seller
        $db->update('KhachHang', [
            'VaiTroID' => ROLE_SELLER
        ], 'ID = :id', ['id' => $seller['KhachHangID']]);
        
        // Log activity
        try {
            $activity = new Activity();
            $activity->log($currentUser['ID'], 'approve_seller', 'Duyệt đăng ký seller: ' . $seller['HoTenChuTro'], [
                'seller_id' => $sellerId,
                'customer_id' => $seller['KhachHangID']
            ]);
        } catch (Exception $e) {
            writeLog("Activity log error: " . $e->getMessage());
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Duyệt đăng ký seller thành công!'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
