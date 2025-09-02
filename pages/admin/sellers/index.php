<?php
/**
 * Admin Sellers Management
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;
use Tro365\Core\Database;
use Tro365\Services\DataConsistencyService;
use Tro365\Services\LocationService;
use Tro365\Models\Activity;

$auth = new Auth();
$db = Database::getInstance();
$dataConsistency = new DataConsistencyService();
$locationService = new LocationService();

// Require admin/moderator role
$auth->requireModerator();

$currentUser = $auth->getCurrentUser();

// Get filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }

        $action = $_POST['action'] ?? '';
        $sellerId = (int)($_POST['seller_id'] ?? 0);

        switch ($action) {
            case 'approve':
                // Approve seller registration
                $db->update('DangKySeller', [
                    'TrangThai' => 1,
                    'NguoiDuyet' => $currentUser['ID'],
                    'NgayDuyet' => date('Y-m-d H:i:s')
                ], 'ID = :id', ['id' => $sellerId]);

                // Update user role to seller
                $sellerData = $db->selectOne("SELECT ds.KhachHangID, kh.HoTen FROM DangKySeller ds JOIN KhachHang kh ON ds.KhachHangID = kh.ID WHERE ds.ID = :id", ['id' => $sellerId]);
                if ($sellerData) {
                    $db->update('KhachHang', [
                        'VaiTroID' => ROLE_SELLER
                    ], 'ID = :id', ['id' => $sellerData['KhachHangID']]);

                    // Log activity
                    try {
                        $activity = new Activity();
                        $activity->log(
                            $currentUser['ID'],
                            'admin_approve_seller',
                            'Duyệt đăng ký seller: ' . ($sellerData['HoTen'] ?? 'ID #' . $sellerData['KhachHangID']),
                            ['seller_id' => $sellerId, 'user_id' => $sellerData['KhachHangID']]
                        );
                    } catch (Exception $e) {
                        // Silent fail for activity logging
                    }
                }

                setFlashMessage(MSG_SUCCESS, 'Duyệt đăng ký seller thành công');
                break;

            case 'reject':
                $reason = $_POST['reason'] ?? '';
                if (empty($reason)) {
                    throw new Exception('Vui lòng nhập lý do từ chối');
                }

                // Get seller info for logging
                $sellerData = $db->selectOne("SELECT ds.KhachHangID, kh.HoTen FROM DangKySeller ds JOIN KhachHang kh ON ds.KhachHangID = kh.ID WHERE ds.ID = :id", ['id' => $sellerId]);

                $db->update('DangKySeller', [
                    'TrangThai' => 2,
                    'NguoiDuyet' => $currentUser['ID'],
                    'NgayDuyet' => date('Y-m-d H:i:s'),
                    'LyDoTuChoi' => $reason
                ], 'ID = :id', ['id' => $sellerId]);

                // Log activity
                try {
                    $activity = new Activity();
                    $activity->log(
                        $currentUser['ID'],
                        'admin_reject_seller',
                        'Từ chối đăng ký seller: ' . ($sellerData['HoTen'] ?? 'ID #' . $sellerData['KhachHangID']) . ' - Lý do: ' . $reason,
                        ['seller_id' => $sellerId, 'user_id' => $sellerData['KhachHangID'], 'reason' => $reason]
                    );
                } catch (Exception $e) {
                    // Silent fail for activity logging
                }

                setFlashMessage(MSG_SUCCESS, 'Từ chối đăng ký seller thành công');
                break;

            case 'change_status':
                $newStatus = (int)($_POST['new_status'] ?? 0);

                if ($newStatus == 0) {
                    // Get current seller data with user info
                    $sellerData = $db->selectOne("SELECT ds.KhachHangID, ds.TrangThai, kh.HoTen FROM DangKySeller ds JOIN KhachHang kh ON ds.KhachHangID = kh.ID WHERE ds.ID = :id", ['id' => $sellerId]);
                    if (!$sellerData) {
                        throw new Exception('Không tìm thấy đăng ký seller');
                    }

                    // Change back to pending - reset approval data
                    $db->update('DangKySeller', [
                        'TrangThai' => 0,
                        'NguoiDuyet' => null,
                        'NgayDuyet' => null,
                        'LyDoTuChoi' => null
                    ], 'ID = :id', ['id' => $sellerId]);

                    // If changing from approved to pending, also update user role back to user
                    if ($sellerData['TrangThai'] == 1) {
                        $db->update('KhachHang', [
                            'VaiTroID' => ROLE_USER
                        ], 'ID = :id', ['id' => $sellerData['KhachHangID']]);
                    }

                    // Log activity
                    try {
                        $activity = new Activity();
                        $statusText = $sellerData['TrangThai'] == 1 ? 'đã duyệt' : 'bị từ chối';
                        $activity->log(
                            $currentUser['ID'],
                            'admin_reset_seller_status',
                            'Reset trạng thái seller về chờ duyệt: ' . ($sellerData['HoTen'] ?? 'ID #' . $sellerData['KhachHangID']) . ' (từ ' . $statusText . ')',
                            ['seller_id' => $sellerId, 'user_id' => $sellerData['KhachHangID'], 'old_status' => $sellerData['TrangThai']]
                        );
                    } catch (Exception $e) {
                        // Silent fail for activity logging
                    }

                    setFlashMessage(MSG_SUCCESS, 'Đã chuyển trạng thái về chờ duyệt');
                } else {
                    throw new Exception('Trạng thái không hợp lệ');
                }
                break;
        }

        redirect('/admin/sellers');

    } catch (Exception $e) {
        setFlashMessage(MSG_ERROR, $e->getMessage());
    }
}

// Build query conditions
$conditions = [];
$params = [];

if ($status !== '' && is_numeric($status)) {
    $conditions[] = "ds.TrangThai = :status";
    $params['status'] = (int)$status;
}

if ($search) {
    $conditions[] = "(ds.HoTenChuTro LIKE :search1 OR kh.Email LIKE :search2 OR ds.SDTLienHe LIKE :search3)";
    $params['search1'] = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
    $params['search3'] = '%' . $search . '%';
}

$whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get sellers with pagination and complete user data
$offset = ($page - 1) * $limit;
$sql = "SELECT ds.*,
               kh.HoTen, kh.Email, kh.TenDN, kh.SDT as UserSDT,
               kh.DiaChi as UserDiaChi, kh.CCCD as UserCCCD,
               duyet.HoTen as NguoiDuyetTen
        FROM DangKySeller ds
        LEFT JOIN KhachHang kh ON ds.KhachHangID = kh.ID
        LEFT JOIN KhachHang duyet ON ds.NguoiDuyet = duyet.ID
        $whereClause
        ORDER BY ds.NgayTao DESC
        LIMIT :limit OFFSET :offset";

$params['limit'] = $limit;
$params['offset'] = $offset;

$sellers = $db->select($sql, $params);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total
             FROM DangKySeller ds
             LEFT JOIN KhachHang kh ON ds.KhachHangID = kh.ID
             $whereClause";
$totalCount = $db->selectOne($countSql, array_diff_key($params, ['limit' => '', 'offset' => '']))['total'] ?? 0;
$totalPages = ceil($totalCount / $limit);

// Get statistics
$stats = [
    'total' => $db->selectOne("SELECT COUNT(*) as count FROM DangKySeller")['count'] ?? 0,
    'pending' => $db->selectOne("SELECT COUNT(*) as count FROM DangKySeller WHERE TrangThai = 0")['count'] ?? 0,
    'approved' => $db->selectOne("SELECT COUNT(*) as count FROM DangKySeller WHERE TrangThai = 1")['count'] ?? 0,
    'rejected' => $db->selectOne("SELECT COUNT(*) as count FROM DangKySeller WHERE TrangThai = 2")['count'] ?? 0
];

// Get flash message
$flash = getFlashMessage();

$pageTitle = 'Quản lý Seller';
include __DIR__ . '/../../../includes/layouts/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar">
                <?php include __DIR__ . '/../../../includes/layouts/admin/sidebar.php'; ?>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 main-content">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="/admin">
                            <i class="fas fa-home me-1"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <i class="fas fa-store me-1"></i>
                        Quản lý seller
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-store me-3"></i>
                            Quản lý seller
                        </h1>
                        <p class="text-muted mb-0">Quản lý đăng ký và hoạt động của các seller trong hệ thống</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-warning" onclick="showPendingModal()"
                                <?= $stats['pending'] == 0 ? 'disabled' : '' ?>>
                            <i class="fas fa-clock me-2"></i>
                            Chờ duyệt (<?= $stats['pending'] ?>)
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="exportSellers()">
                            <i class="fas fa-download me-2"></i>Xuất danh sách
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="refreshSellers()">
                            <i class="fas fa-sync-alt me-2"></i>Làm mới
                        </button>
                        <button type="button" class="btn btn-primary" onclick="bulkActions()">
                            <i class="fas fa-tasks me-2"></i>Thao tác hàng loạt
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === MSG_SUCCESS ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h4 class="mb-1"><?= number_format($stats['total']) ?></h4>
                            <p class="text-muted mb-0">Tổng đăng ký</p>
                            <i class="fas fa-users card-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h4 class="mb-1 text-warning"><?= number_format($stats['pending']) ?></h4>
                            <p class="text-muted mb-0">Chờ duyệt</p>
                            <i class="fas fa-clock card-icon text-warning"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h4 class="mb-1 text-success"><?= number_format($stats['approved']) ?></h4>
                            <p class="text-muted mb-0">Đã duyệt</p>
                            <i class="fas fa-check-circle card-icon text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h4 class="mb-1 text-danger"><?= number_format($stats['rejected']) ?></h4>
                            <p class="text-muted mb-0">Từ chối</p>
                            <i class="fas fa-times-circle card-icon text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Chờ duyệt</option>
                                <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Đã duyệt</option>
                                <option value="2" <?= $status === '2' ? 'selected' : '' ?>>Từ chối</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Tìm theo tên, email, số điện thoại..."
                                   value="<?= e($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Lọc
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sellers List -->
            <div class="card sellers-list-card admin-header-mobile admin-table-mobile">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Danh sách đăng ký Seller (<?= number_format($totalCount) ?> kết quả)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($sellers)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>Không có đăng ký seller nào</h5>
                            <p class="text-muted">Chưa có đăng ký seller nào được tạo</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Thông tin chủ trọ</th>
                                        <th>Liên hệ</th>
                                        <th>Địa chỉ</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày đăng ký</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sellers as $seller):
                                        // Get effective values (seller-specific data takes priority over user data)
                                        $effectiveCCCD = ($seller['SoCCCD'] ?? '') ?: ($seller['CCCD'] ?? '');
                                        $effectivePhone = ($seller['SDTLienHe'] ?? '') ?: ($seller['SDT'] ?? '');
                                        $effectiveEmail = ($seller['EmailLienHe'] ?? '') ?: ($seller['Email'] ?? '');
                                        $effectiveAddress = ($seller['DiaChiKinhDoanh'] ?? '') ?: ($seller['DiaChi'] ?? '');
                                    ?>
                                        <tr>
                                            <td><?= $seller['ID'] ?></td>
                                            <td>
                                                <div>
                                                    <strong><?= e($seller['HoTenChuTro']) ?></strong>
                                                </div>
                                                <small class="text-muted">
                                                    CCCD: <?= e($effectiveCCCD) ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    User: <?= e($seller['TenDN']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div><?= e($effectivePhone) ?></div>
                                                <small class="text-muted"><?= e($effectiveEmail) ?></small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?= e($effectiveAddress) ?>
                                                    <?php
                                                    $locationText = '';
                                                    if ($seller['XaPhuongID']) {
                                                        $ward = $locationService->getWardById($seller['XaPhuongID']);
                                                        $locationText .= $ward['name'] ?? '';
                                                    }
                                                    if ($seller['QuanHuyenID']) {
                                                        $district = $locationService->getDistrictById($seller['QuanHuyenID']);
                                                        $locationText .= ($locationText ? ', ' : '') . ($district['name'] ?? '');
                                                    }
                                                    if ($seller['TinhThanhID']) {
                                                        $province = $locationService->getProvinceById($seller['TinhThanhID']);
                                                        $locationText .= ($locationText ? ', ' : '') . ($province['name'] ?? '');
                                                    }
                                                    if ($locationText): ?>
                                                        <br><?= e($locationText) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($seller['TrangThai'] == 0): ?>
                                                    <span class="badge bg-warning">Chờ duyệt</span>
                                                <?php elseif ($seller['TrangThai'] == 1): ?>
                                                    <span class="badge bg-success">Đã duyệt</span>
                                                    <?php if ($seller['NgayDuyet']): ?>
                                                        <br><small class="text-muted">
                                                            <?= formatDateTime($seller['NgayDuyet']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Từ chối</span>
                                                    <?php if ($seller['LyDoTuChoi']): ?>
                                                        <br><small class="text-muted" title="<?= e($seller['LyDoTuChoi']) ?>">
                                                            <?= e(truncateText($seller['LyDoTuChoi'], 30)) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= formatDateTime($seller['NgayTao']) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-info"
                                                            onclick="viewSellerDetails(<?= $seller['KhachHangID'] ?>)"
                                                            title="Xem chi tiết người dùng">
                                                        <i class="fas fa-eye"></i>
                                                    </button>

                                                    <?php if ($seller['TrangThai'] == 0): ?>
                                                        <button type="button" class="btn btn-outline-success"
                                                                onclick="approveSeller(<?= $seller['ID'] ?>)"
                                                                title="Duyệt đăng ký">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger"
                                                                onclick="rejectSeller(<?= $seller['ID'] ?>)"
                                                                title="Từ chối đăng ký">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($seller['TrangThai'] == 1): ?>
                                                        <button type="button" class="btn btn-outline-warning"
                                                                onclick="changeSellerStatus(<?= $seller['ID'] ?>, 0)"
                                                                title="Chuyển về chờ duyệt">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger"
                                                                onclick="rejectSeller(<?= $seller['ID'] ?>)"
                                                                title="Từ chối đăng ký">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php elseif ($seller['TrangThai'] == 2): ?>
                                                        <button type="button" class="btn btn-outline-warning"
                                                                onclick="changeSellerStatus(<?= $seller['ID'] ?>, 0)"
                                                                title="Chuyển về chờ duyệt">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-success"
                                                                onclick="approveSeller(<?= $seller['ID'] ?>)"
                                                                title="Duyệt đăng ký">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Forms -->
<form id="approveForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="seller_id" id="approveSellerId">
</form>

<form id="changeStatusForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="change_status">
    <input type="hidden" name="seller_id" id="changeStatusSellerId">
    <input type="hidden" name="new_status" id="changeStatusNewStatus">
</form>

<!-- Pending Sellers Modal -->
<div class="modal fade" id="pendingModal" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clock me-2 text-warning"></i>
                    Danh sách seller chờ duyệt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="pendingSellersList">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                        <p class="mt-2 text-muted">Đang tải danh sách seller chờ duyệt...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="refreshPendingList()">
                    <i class="fas fa-sync-alt me-2"></i>Làm mới
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Từ chối đăng ký Seller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="seller_id" id="rejectSellerId">

                    <div class="mb-3">
                        <label class="form-label">Lý do từ chối <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required
                                  placeholder="Nhập lý do từ chối đăng ký seller..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewSellerDetails(userId) {
    // Redirect to user info page
    window.location.href = '/admin/users/info?id=' + userId;
}

function approveSeller(sellerId) {
    if (confirm('Bạn có chắc chắn muốn duyệt đăng ký seller này?')) {
        fetch('/admin/sellers/approve-ajax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                seller_id: sellerId,
                csrf_token: '<?= csrf_token() ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Duyệt seller thành công', 'success');
                loadPendingSellers();
                refreshSellersStats();
            } else {
                showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(() => showToast('Không thể duyệt seller', 'error'));
    }
}

function rejectSeller(sellerId) {
    document.getElementById('rejectSellerId').value = sellerId;
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

function changeSellerStatus(sellerId, newStatus) {
    let confirmMessage = '';
    if (newStatus == 0) {
        confirmMessage = 'Bạn có chắc chắn muốn chuyển trạng thái về "Chờ duyệt"?\n\nHành động này sẽ reset tất cả thông tin duyệt trước đó.';
    }

    if (!confirmMessage || confirm(confirmMessage)) {
        fetch('/admin/sellers/change-status-ajax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                seller_id: sellerId,
                new_status: newStatus,
                csrf_token: '<?= csrf_token() ?>'
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Cập nhật trạng thái thành công', 'success');
                loadPendingSellers();
                refreshSellersStats();
            } else {
                showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(() => showToast('Không thể cập nhật trạng thái', 'error'));
    }
}

// Show pending sellers modal
function showPendingModal() {
    const modalElement = document.getElementById('pendingModal');
    if (!modalElement) {
        alert('Không tìm thấy modal');
        return;
    }

    const modal = new bootstrap.Modal(modalElement, {
        backdrop: false,
        keyboard: true
    });

    modal.show();
    loadPendingSellers();
}

// Load pending sellers via AJAX
function loadPendingSellers() {
    const container = document.getElementById('pendingSellersList');

    fetch('/admin/sellers/pending-ajax', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            container.innerHTML = data.html;
        } else {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${data.message || 'Có lỗi xảy ra khi tải danh sách'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Không thể tải danh sách seller chờ duyệt
            </div>
        `;
    });
}

// Refresh pending list
function refreshPendingList() {
    loadPendingSellers();
}

// Approve seller from modal
function approveSellerModal(sellerId) {
    if (confirm('Bạn có chắc chắn muốn duyệt đăng ký seller này?')) {
        fetch('/admin/sellers/approve-ajax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                seller_id: sellerId,
                csrf_token: '<?= csrf_token() ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh the pending list
                loadPendingSellers();
                // Show success message
                showToast(data.message || 'Duyệt seller thành công', 'success');
                // Soft refresh stats area only (avoid full reload)
                refreshSellersStats();
            } else {

function refreshSellersStats(){
    // Soft refresh the stats counters (no full page reload)
    fetch('/admin/sellers?'+new URLSearchParams(window.location.search),{headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(r=>r.text())
      .then(html=>{
        const tmp=document.createElement('div'); tmp.innerHTML=html;
        const newRow=tmp.querySelector('.row.mb-4'); // stats row block
        const oldRow=document.querySelector('.row.mb-4');
        if(newRow && oldRow){ oldRow.replaceWith(newRow); }
      })
      .catch(()=>{});
}

                showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Không thể duyệt seller');
        });
    }
}

// Reject seller from modal
function rejectSellerModal(sellerId) {
    const reason = prompt('Nhập lý do từ chối:');
    if (reason && reason.trim()) {
        fetch('/admin/sellers/reject-ajax', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                seller_id: sellerId,
                reason: reason.trim(),
                csrf_token: '<?= csrf_token() ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Refresh the pending list
                loadPendingSellers();
                // Show success message
                showToast(data.message || 'Từ chối seller thành công', 'success');
                // Soft refresh stats area only (avoid full reload)
                refreshSellersStats();
            } else {
                showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Không thể từ chối seller');
        });
    }
}

// Show toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';

    const toastHtml = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();

    // Remove toast after it's hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Create toast container if it doesn't exist
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed end-0 p-3';
    container.style.top = '100px';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Auto-submit form when status filter changes
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.querySelector('select[name="status"]');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
});

// Other utility functions
function exportSellers() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "ID,Tên chủ trọ,CCCD,Số điện thoại,Email,Địa chỉ,Trạng thái,Ngày đăng ký\n";

    // Get table data
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            const id = cells[0].textContent.trim();
            const name = cells[1].querySelector('strong').textContent.trim();
            const cccd = cells[1].textContent.match(/CCCD: (.+)/)?.[1] || '';
            const phone = cells[2].querySelector('div').textContent.trim();
            const email = cells[2].querySelector('small').textContent.trim();
            const address = cells[3].textContent.trim();
            const status = cells[4].querySelector('span').textContent.trim();
            const date = cells[5].textContent.trim();

            csvContent += `"${id}","${name}","${cccd}","${phone}","${email}","${address}","${status}","${date}"\n`;
        }
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "danh-sach-seller-" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function refreshSellers() {
    window.location.reload();
}

function bulkActions() {
    alert('Chức năng thao tác hàng loạt sẽ được phát triển trong phiên bản tiếp theo.');
}


</script>

<?php include __DIR__ . '/../../../includes/layouts/admin/footer.php'; ?>

<style>
/* Simple modal styling without backdrop */
.modal {
    z-index: 1050 !important;
}

.modal.show {
    display: block !important;
}

.modal-content {
    pointer-events: auto;
}

.modal-dialog {
    pointer-events: auto;
}
</style>
