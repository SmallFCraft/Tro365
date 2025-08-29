<?php
/**
 * AJAX endpoint for pending sellers list
 * Tro365 - Website thuê trọ
 */

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Include necessary files
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../includes/functions/helpers.php';

use Tro365\Core\Auth;
use Tro365\Core\Database;
use Tro365\DataConsistency;

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Initialize with error handling
try {
    $auth = new Auth();
    $db = Database::getInstance();
    $dataConsistency = new DataConsistency();

    // Require admin/moderator role
    $auth->requireModerator();
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập: ' . $e->getMessage()]);
    exit;
}

try {
    // Get pending sellers with complete user data
    $sql = "SELECT ds.*,
                   kh.HoTen, kh.Email, kh.TenDN, kh.SDT,
                   kh.DiaChi, kh.CCCD
            FROM DangKySeller ds
            LEFT JOIN KhachHang kh ON ds.KhachHangID = kh.ID
            WHERE ds.TrangThai = 0
            ORDER BY ds.NgayTao ASC";

    $pendingSellers = $db->select($sql);
    
    if (empty($pendingSellers)) {
        $html = '
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="text-muted">Không có seller nào chờ duyệt</h5>
                <p class="text-muted">Tất cả đăng ký seller đã được xử lý</p>
            </div>
        ';
    } else {
        $html = '
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="8%">ID</th>
                            <th width="25%">Thông tin chủ trọ</th>
                            <th width="20%">Liên hệ</th>
                            <th width="25%">Địa chỉ</th>
                            <th width="12%">Ngày đăng ký</th>
                            <th width="10%" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach ($pendingSellers as $seller) {
            // Get effective values (seller-specific data takes priority over user data)
            $effectiveCCCD = ($seller['SoCCCD'] ?? '') ?: ($seller['CCCD'] ?? '');
            $effectivePhone = ($seller['SDTLienHe'] ?? '') ?: ($seller['SDT'] ?? '');
            $effectiveEmail = ($seller['EmailLienHe'] ?? '') ?: ($seller['Email'] ?? '');
            $effectiveAddress = ($seller['DiaChiKinhDoanh'] ?? '') ?: ($seller['DiaChi'] ?? '');

            $html .= '
                        <tr>
                            <td>
                                <span class="fw-bold text-primary">#' . ($seller['ID'] ?? '') . '</span>
                            </td>
                            <td>
                                <div>
                                    <strong>' . e($seller['HoTenChuTro'] ?? $seller['HoTen'] ?? '') . '</strong>
                                </div>
                                <small class="text-muted">
                                    CCCD: ' . e($effectiveCCCD) . '
                                </small>
                                <br>
                                <small class="text-muted">
                                    User: ' . e($seller['TenDN'] ?? '') . '
                                </small>
                            </td>
                            <td>
                                <div>' . e($effectivePhone) . '</div>
                                <small class="text-muted">' . e($effectiveEmail) . '</small>
                            </td>
                            <td>
                                <small>
                                    ' . e($effectiveAddress) . '';

            if (($seller['TenXP'] ?? '') || ($seller['TenQH'] ?? '') || ($seller['TenTT'] ?? '')) {
                $html .= '<br>' . e(($seller['TenXP'] ?? '') . ', ' . ($seller['TenQH'] ?? '') . ', ' . ($seller['TenTT'] ?? ''));
            }
            
            $html .= '
                                </small>
                            </td>
                            <td>
                                <small>' . date('d/m/Y H:i', strtotime($seller['NgayTao'])) . '</small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-info" 
                                            onclick="viewSellerDetails(' . $seller['ID'] . ')"
                                            title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-success" 
                                            onclick="approveSellerModal(' . $seller['ID'] . ')"
                                            title="Duyệt">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="rejectSellerModal(' . $seller['ID'] . ')"
                                            title="Từ chối">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Tổng cộng:</strong> ' . count($pendingSellers) . ' seller chờ duyệt
                </div>
            </div>
        ';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($pendingSellers)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?>
