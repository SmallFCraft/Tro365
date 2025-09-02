<?php
/**
 * AJAX endpoint for changing seller status (pending<->approved/rejected)
 */

use Tro365\Core\Auth;
use Tro365\Core\Database;
use Tro365\Activity;

header('Content-Type: application/json; charset=utf-8');

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$auth = new Auth();
$db = Database::getInstance();

try {
    $auth->requireModerator();
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$currentUser = $auth->getCurrentUser();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) throw new Exception('Dữ liệu không hợp lệ');

    $sellerId = (int)($input['seller_id'] ?? 0);
    $newStatus = (int)($input['new_status'] ?? -1);
    $csrfToken = $input['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) throw new Exception('Token bảo mật không hợp lệ');
    if (!$sellerId) throw new Exception('ID seller không hợp lệ');
    if (!in_array($newStatus, [0,1,2], true)) throw new Exception('Trạng thái không hợp lệ');

    $seller = $db->selectOne('SELECT * FROM DangKySeller WHERE ID=:id', ['id'=>$sellerId]);
    if (!$seller) throw new Exception('Không tìm thấy seller');

    $db->update('DangKySeller', [
        'TrangThai' => $newStatus,
        'NguoiDuyet' => $currentUser['ID'],
        'NgayDuyet' => date('Y-m-d H:i:s')
    ], 'ID = :id', ['id' => $sellerId]);

    // Optional: if set to approved, also ensure user role is Seller
    if ($newStatus === 1) {
        $db->update('KhachHang', ['VaiTroID' => ROLE_SELLER], 'ID = :id', ['id' => $seller['KhachHangID']]);
    }

    // Activity log
    try {
        $activity = new Activity();
        $activity->log($currentUser['ID'], 'change_seller_status', 'Cập nhật trạng thái đăng ký seller', [
            'seller_id' => $sellerId,
            'new_status' => $newStatus,
        ]);
    } catch (Exception $e) {}

    echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

