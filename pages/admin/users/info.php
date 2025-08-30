<?php
/**
 * Admin User Info
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;
use Tro365\Core\Database;
use Tro365\Services\LocationService;

$auth = new Auth();
$db = Database::getInstance();

// Require admin role
$auth->requireAdmin();

$currentUser = $auth->getCurrentUser();

// Get user ID
$userId = (int)($_GET['id'] ?? 0);
if (!$userId) {
    setFlashMessage(MSG_ERROR, 'ID người dùng không hợp lệ');
    redirect('/admin/users');
    exit;
}

// Get user data with role info
$sql = "SELECT kh.*, vt.TenVT, vt.CapDo
        FROM KhachHang kh
        LEFT JOIN VaiTro vt ON kh.VaiTroID = vt.ID
        WHERE kh.ID = :id";

$userData = $db->selectOne($sql, ['id' => $userId]);

if (!$userData) {
    setFlashMessage(MSG_ERROR, 'Không tìm thấy người dùng');
    redirect('/admin/users');
    exit;
}

// Get location names using LocationService
$locationService = new LocationService();
$userData['TenTT'] = '';
$userData['TenQH'] = '';
$userData['TenXP'] = '';

if ($userData['TinhThanhID']) {
    $province = $locationService->getProvinceById($userData['TinhThanhID']);
    $userData['TenTT'] = $province['name'] ?? '';
}
if ($userData['QuanHuyenID']) {
    $district = $locationService->getDistrictById($userData['QuanHuyenID']);
    $userData['TenQH'] = $district['name'] ?? '';
}
if ($userData['XaPhuongID']) {
    $ward = $locationService->getWardById($userData['XaPhuongID']);
    $userData['TenXP'] = $ward['name'] ?? '';
}

// Get user statistics - Simplified for debugging
$stats = [
    'posts' => 0,
    'posts_approved' => 0,
    'posts_pending' => 0,
    'transactions' => 0,
    'favorites' => 0,
    'contacts_sent' => 0,
    'contacts_received' => 0
];

// Try each query individually to identify the problematic one
try {
    $result = $db->selectOne("SELECT COUNT(*) as count FROM BaiDang WHERE NguoiDangID = :id", ['id' => $userId]);
    $stats['posts'] = $result['count'] ?? 0;
} catch (Exception $e) {
    writeLog("Posts query error: " . $e->getMessage());
}

try {
    $result = $db->selectOne("SELECT COUNT(*) as count FROM BaiDang WHERE NguoiDangID = :id AND TrangThai = :status", ['id' => $userId, 'status' => POST_STATUS_APPROVED]);
    $stats['posts_approved'] = $result['count'] ?? 0;
} catch (Exception $e) {
    writeLog("Posts approved query error: " . $e->getMessage());
}

try {
    $result = $db->selectOne("SELECT COUNT(*) as count FROM BaiDang WHERE NguoiDangID = :id AND TrangThai = :status", ['id' => $userId, 'status' => POST_STATUS_PENDING]);
    $stats['posts_pending'] = $result['count'] ?? 0;
} catch (Exception $e) {
    writeLog("Posts pending query error: " . $e->getMessage());
}

// Get seller registration if exists
$sellerRegistration = null;
if ($userData['VaiTroID'] >= ROLE_SELLER) {
    try {
        $sellerRegistration = $db->selectOne("SELECT * FROM DangKySeller WHERE KhachHangID = :id ORDER BY NgayTao DESC LIMIT 1", ['id' => $userId]);
    } catch (Exception $e) {
        writeLog("Seller registration query error: " . $e->getMessage());
        $sellerRegistration = null;
    }
}

// Get recent activities
try {
    $recentPosts = $db->select("SELECT ID, TieuDe, TrangThai, NgayTao FROM BaiDang WHERE NguoiDangID = :id ORDER BY NgayTao DESC LIMIT 5", ['id' => $userId]);
} catch (Exception $e) {
    writeLog("Recent posts query error: " . $e->getMessage());
    $recentPosts = [];
}

// Get recent transactions
try {
    $recentTransactions = $db->select("
        SELECT gt.*, bd.TieuDe as TenBaiDang
        FROM GiaoDich gt
        LEFT JOIN BaiDang bd ON gt.BaiDangID = bd.ID
        WHERE gt.NguoiThueID = :nguoi_thue_id OR gt.ChuNhaID = :chu_nha_id
        ORDER BY gt.NgayTao DESC LIMIT 5
    ", ['nguoi_thue_id' => $userId, 'chu_nha_id' => $userId]);
} catch (Exception $e) {
    writeLog("Recent transactions query error: " . $e->getMessage());
    $recentTransactions = [];
}

$pageTitle = 'Thông tin người dùng: ' . $userData['HoTen'];
$pageDescription = 'Chi tiết thông tin người dùng trong hệ thống';

include_once __DIR__ . '/../../../includes/layouts/admin/header.php';
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
                    <li class="breadcrumb-item">
                        <a href="/admin/users">
                            <i class="fas fa-users me-1"></i>
                            Quản lý người dùng
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <i class="fas fa-user me-1"></i>
                        <?= e($userData['HoTen']) ?>
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-user me-3"></i>
                            Thông tin người dùng
                        </h1>
                        <p class="text-muted mb-0">Chi tiết thông tin của: <strong><?= e($userData['HoTen']) ?></strong></p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="/admin/users" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Quay lại
                        </a>
                        <a href="/admin/users/edit?id=<?= $userId ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>
                            Chỉnh sửa
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Info Cards -->
            <div class="row">
                <!-- Basic Info -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-id-card me-2"></i>
                                Thông tin cơ bản
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%" class="fw-bold">ID:</td>
                                            <td>#<?= $userData['ID'] ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Họ tên:</td>
                                            <td><?= e($userData['HoTen']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Tên đăng nhập:</td>
                                            <td><?= e($userData['TenDN']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Email:</td>
                                            <td><?= e($userData['Email']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Số điện thoại:</td>
                                            <td><?= e($userData['SDT'] ?: 'Chưa cập nhật') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">CCCD:</td>
                                            <td><?= e($userData['CCCD'] ?: 'Chưa cập nhật') ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%" class="fw-bold">Ngày sinh:</td>
                                            <td><?= $userData['NgaySinh'] ? date('d/m/Y', strtotime($userData['NgaySinh'])) : 'Chưa cập nhật' ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Giới tính:</td>
                                            <td><?= e($userData['GioiTinh'] ?: 'Chưa cập nhật') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Vai trò:</td>
                                            <td>
                                                <span class="badge bg-<?= getRoleBadgeColor($userData['CapDo']) ?>">
                                                    <?= e($userData['TenVT']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Trạng thái:</td>
                                            <td>
                                                <?php if ($userData['TrangThai'] == 1): ?>
                                                    <span class="badge bg-success">Hoạt động</span>
                                                <?php elseif ($userData['TrangThai'] == 0): ?>
                                                    <span class="badge bg-warning">Tạm khóa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Bị cấm</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Ngày tạo:</td>
                                            <td><?= date('d/m/Y H:i', strtotime($userData['NgayTao'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Lần đăng nhập cuối:</td>
                                            <td><?= $userData['LanDangNhapCuoi'] ? date('d/m/Y H:i', strtotime($userData['LanDangNhapCuoi'])) : 'Chưa đăng nhập' ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                Thông tin địa chỉ
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($userData['DiaChi'] || $userData['TenTT']): ?>
                                <p class="mb-2">
                                    <strong>Địa chỉ:</strong> <?= e($userData['DiaChi'] ?: 'Chưa cập nhật') ?>
                                </p>
                                <?php if ($userData['TenTT']): ?>
                                    <p class="mb-0">
                                        <strong>Khu vực:</strong> 
                                        <?= e($userData['TenXP'] . ', ' . $userData['TenQH'] . ', ' . $userData['TenTT']) ?>
                                    </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">Chưa cập nhật thông tin địa chỉ</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Seller Registration Info -->
                    <?php if ($sellerRegistration): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-store me-2"></i>
                                    Thông tin đăng ký Seller
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Tên chủ trọ:</strong><br>
                                            <?= e($sellerRegistration['HoTenChuTro']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <strong>CCCD:</strong><br>
                                            <?= e($sellerRegistration['CCCD']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <strong>SĐT liên hệ:</strong><br>
                                            <?= e($sellerRegistration['SDTLienHe']) ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Trạng thái:</strong><br>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            switch ($sellerRegistration['TrangThai']) {
                                                case 0:
                                                    $statusClass = 'warning';
                                                    $statusText = 'Chờ duyệt';
                                                    break;
                                                case 1:
                                                    $statusClass = 'success';
                                                    $statusText = 'Đã duyệt';
                                                    break;
                                                case 2:
                                                    $statusClass = 'danger';
                                                    $statusText = 'Từ chối';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                        </p>
                                        <p class="mb-2">
                                            <strong>Ngày đăng ký:</strong><br>
                                            <?= date('d/m/Y H:i', strtotime($sellerRegistration['NgayTao'])) ?>
                                        </p>
                                        <?php if ($sellerRegistration['NgayDuyet']): ?>
                                            <p class="mb-0">
                                                <strong>Ngày duyệt:</strong><br>
                                                <?= date('d/m/Y H:i', strtotime($sellerRegistration['NgayDuyet'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Statistics & Avatar -->
                <div class="col-md-4">
                    <!-- Avatar -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-image me-2"></i>
                                Ảnh đại diện
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <?= getUserAvatarHtml(
                                $userData['AnhDaiDien'],
                                'img-fluid rounded-circle mb-3',
                                'Avatar',
                                'max-width: 150px;'
                            ) ?>
                            <p class="text-muted mb-0">
                                <?= $userData['AnhDaiDien'] ? 'Đã có ảnh đại diện' : 'Chưa có ảnh đại diện' ?>
                            </p>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Thống kê hoạt động
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Main stats -->
                            <div class="row text-center mb-4">
                                <div class="col-4">
                                    <h4 class="text-primary"><?= number_format($stats['posts']) ?></h4>
                                    <small class="text-muted">Tổng bài đăng</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-success"><?= number_format($stats['transactions']) ?></h4>
                                    <small class="text-muted">Giao dịch</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-warning"><?= number_format($stats['favorites']) ?></h4>
                                    <small class="text-muted">Yêu thích</small>
                                </div>
                            </div>

                            <!-- Detailed stats -->
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h6 class="text-success"><?= number_format($stats['posts_approved']) ?></h6>
                                        <small class="text-muted">Bài đã duyệt</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h6 class="text-warning"><?= number_format($stats['posts_pending']) ?></h6>
                                    <small class="text-muted">Bài chờ duyệt</small>
                                </div>
                            </div>

                            <hr class="my-3">

                            <!-- Contact stats -->
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h6 class="text-info"><?= number_format($stats['contacts_sent']) ?></h6>
                                        <small class="text-muted">Liên hệ gửi</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h6 class="text-secondary"><?= number_format($stats['contacts_received']) ?></h6>
                                    <small class="text-muted">Liên hệ nhận</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row mt-4">
                <!-- Recent Posts -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-newspaper me-2"></i>
                                Bài đăng gần đây
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentPosts)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentPosts as $post): ?>
                                        <div class="list-group-item px-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <a href="/post/<?= $post['ID'] ?>" target="_blank" class="text-decoration-none">
                                                            <?= e(truncateText($post['TieuDe'], 50)) ?>
                                                        </a>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($post['NgayTao'])) ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-<?= getPostStatusColor($post['TrangThai']) ?>">
                                                    <?= getPostStatusText($post['TrangThai']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="/admin/posts?user_id=<?= $userId ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Xem tất cả
                                    </a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">Chưa có bài đăng nào</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-exchange-alt me-2"></i>
                                Giao dịch gần đây
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentTransactions)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                        <div class="list-group-item px-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <?= e(truncateText($transaction['TenBaiDang'] ?? 'Bài đăng #' . $transaction['BaiDangID'], 40)) ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($transaction['NgayTao'])) ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-<?= getTransactionStatusColor($transaction['TrangThai']) ?>">
                                                    <?= getTransactionStatusText($transaction['TrangThai']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="/admin/transactions?user_id=<?= $userId ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Xem tất cả
                                    </a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-0">Chưa có giao dịch nào</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/layouts/admin/footer.php'; ?>

<style>
/* User info page styling */
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.list-group-item {
    border: none;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.list-group-item:last-child {
    border-bottom: none;
}

.badge {
    font-size: 0.75rem;
}

.text-center h4, .text-center h6 {
    margin-bottom: 0.25rem;
}

.border-end {
    border-right: 1px solid #dee2e6 !important;
}

/* Avatar styling */
.rounded-circle {
    border: 3px solid #e9ecef;
}

/* Stats cards */
.card-body .row.text-center {
    margin-bottom: 0;
}

.card-body .row.text-center:not(:last-child) {
    margin-bottom: 1rem;
}

/* Recent activities */
.list-group-item:hover {
    background-color: #f8f9fa;
}

.list-group-item a:hover {
    text-decoration: underline !important;
}
</style>

<?php
function getRoleBadgeColor($capDo) {
    switch ($capDo) {
        case 5: return 'danger';   // Admin
        case 4: return 'warning';  // Moderator
        case 3: return 'info';     // Supporter
        case 2: return 'success';  // Seller
        default: return 'secondary'; // User
    }
}

function getPostStatusColor($status) {
    switch ($status) {
        case POST_STATUS_PENDING: return 'warning';
        case POST_STATUS_APPROVED: return 'success';
        case POST_STATUS_REJECTED: return 'danger';
        case POST_STATUS_HIDDEN: return 'secondary';
        default: return 'secondary';
    }
}

function getPostStatusText($status) {
    switch ($status) {
        case POST_STATUS_PENDING: return 'Chờ duyệt';
        case POST_STATUS_APPROVED: return 'Đã duyệt';
        case POST_STATUS_REJECTED: return 'Từ chối';
        case POST_STATUS_HIDDEN: return 'Ẩn';
        default: return 'Không xác định';
    }
}

function getTransactionStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getTransactionStatusText($status) {
    switch ($status) {
        case 'pending': return 'Chờ xử lý';
        case 'confirmed': return 'Đã xác nhận';
        case 'completed': return 'Hoàn thành';
        case 'cancelled': return 'Đã hủy';
        default: return 'Không xác định';
    }
}
?>
