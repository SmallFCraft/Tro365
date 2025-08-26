<?php
/**
 * Admin Users Management Page
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;
use Tro365\Models\User;
use Tro365\Core\Database;

$auth = new Auth();
$user = new User();
$db = Database::getInstance();

// Force refresh session to get latest role
$auth->updateSession();

// Require admin access
$auth->requireModerator();

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }
        
        $action = $_POST['action'] ?? '';
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if (!$userId) {
            throw new Exception('ID người dùng không hợp lệ');
        }
        
        switch ($action) {
            case 'activate':
                $user->update($userId, ['TrangThai' => 1]);
                $success = 'Kích hoạt tài khoản thành công!';
                break;
                
            case 'deactivate':
                $user->update($userId, ['TrangThai' => 0]);
                $success = 'Vô hiệu hóa tài khoản thành công!';
                break;
                
            case 'promote_seller':
                $user->update($userId, ['VaiTroID' => ROLE_SELLER]);
                $success = 'Nâng cấp thành Seller thành công!';
                break;
                
            case 'demote_user':
                $user->update($userId, ['VaiTroID' => ROLE_USER]);
                $success = 'Hạ cấp về User thành công!';
                break;
                
            case 'promote_moderator':
                $user->update($userId, ['VaiTroID' => ROLE_MODERATOR]);
                $success = 'Nâng cấp thành Moderator thành công!';
                break;
                
            case 'promote_admin':
                // Only admin can promote to admin
                $auth->requireAdmin();
                $user->update($userId, ['VaiTroID' => ROLE_ADMIN]);
                $success = 'Nâng cấp thành Admin thành công!';
                break;

            case 'verify_email':
                $user->update($userId, ['email_verified_at' => date('Y-m-d H:i:s')]);
                $success = 'Xác thực email thành công!';
                break;

            case 'unverify_email':
                $user->update($userId, ['email_verified_at' => null]);
                $success = 'Hủy xác thực email thành công!';
                break;

            default:
                throw new Exception('Hành động không hợp lệ');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get filters
$status = $_GET['status'] ?? '';
$role = $_GET['role'] ?? '';
$emailVerified = $_GET['email_verified'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;

// Build query conditions
$conditions = [];
$params = [];

if ($status === 'active') {
    $conditions[] = "kh.TrangThai = 1";
} elseif ($status === 'inactive') {
    $conditions[] = "kh.TrangThai = 0";
}

if ($role && is_numeric($role)) {
    $conditions[] = "kh.VaiTroID = :role";
    $params['role'] = (int)$role;
}

if ($emailVerified === 'verified') {
    $conditions[] = "kh.email_verified_at IS NOT NULL";
} elseif ($emailVerified === 'unverified') {
    $conditions[] = "kh.email_verified_at IS NULL";
}

if ($search) {
    $conditions[] = "(kh.HoTen LIKE :search1 OR kh.Email LIKE :search2 OR kh.TenDN LIKE :search3)";
    $params['search1'] = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
    $params['search3'] = '%' . $search . '%';
}

$whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get users with pagination
$offset = ($page - 1) * $limit;
$sql = "SELECT kh.*, vt.TenVT as TenVaiTro
        FROM KhachHang kh
        LEFT JOIN VaiTro vt ON kh.VaiTroID = vt.ID
        $whereClause
        ORDER BY kh.NgayTao DESC
        LIMIT :limit OFFSET :offset";

$params['limit'] = $limit;
$params['offset'] = $offset;

$users = $db->select($sql, $params);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM KhachHang kh $whereClause";
$countParams = array_diff_key($params, ['limit' => '', 'offset' => '']);
$totalResult = $db->selectOne($countSql, $countParams);
$total = $totalResult['total'] ?? 0;
$totalPages = ceil($total / $limit);

// Get statistics
$stats = [
    'total' => $db->selectOne("SELECT COUNT(*) as count FROM KhachHang")['count'] ?? 0,
    'active' => $db->selectOne("SELECT COUNT(*) as count FROM KhachHang WHERE TrangThai = 1")['count'] ?? 0,
    'sellers' => $db->selectOne("SELECT COUNT(*) as count FROM KhachHang WHERE VaiTroID = " . ROLE_SELLER)['count'] ?? 0,
    'pending' => $db->selectOne("SELECT COUNT(*) as count FROM KhachHang WHERE TrangThai = 0")['count'] ?? 0
];

$pageTitle = 'Quản lý người dùng';
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
                        <i class="fas fa-users me-1"></i>
                        Quản lý người dùng
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-users me-3"></i>
                            Quản lý người dùng
                        </h1>
                        <p class="text-muted mb-0">Quản lý tài khoản và phân quyền người dùng trong hệ thống</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success" onclick="exportUsers()">
                            <i class="fas fa-download me-2"></i>Xuất danh sách
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="refreshUsers()">
                            <i class="fas fa-sync-alt me-2"></i>Làm mới
                        </button>
                        <button type="button" class="btn btn-primary" onclick="addUser()">
                            <i class="fas fa-user-plus me-2"></i>Thêm người dùng
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['total']) ?></h3>
                                    <p class="mb-2">Tổng người dùng</p>
                                    <small>
                                        <i class="fas fa-chart-line me-1"></i>
                                        Tất cả tài khoản
                                    </small>
                                </div>
                                <i class="fas fa-users card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['active']) ?></h3>
                                    <p class="mb-2">Đang hoạt động</p>
                                    <small>
                                        <i class="fas fa-user-check me-1"></i>
                                        Tài khoản active
                                    </small>
                                </div>
                                <i class="fas fa-user-check card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['sellers']) ?></h3>
                                    <p class="mb-2">Seller</p>
                                    <small>
                                        <i class="fas fa-store me-1"></i>
                                        Tài khoản bán hàng
                                    </small>
                                </div>
                                <i class="fas fa-store card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['pending']) ?></h3>
                                    <p class="mb-2">Chờ duyệt</p>
                                    <small>
                                        <i class="fas fa-clock me-1"></i>
                                        Tài khoản inactive
                                    </small>
                                </div>
                                <i class="fas fa-user-clock card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Bộ lọc tìm kiếm
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-4">
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>
                                Trạng thái
                            </label>
                            <select name="status" class="form-select">
                                <option value="">🔍 Tất cả trạng thái</option>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>✅ Hoạt động</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>⏸️ Vô hiệu hóa</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">
                                <i class="fas fa-user-tag me-1"></i>
                                Vai trò
                            </label>
                            <select name="role" class="form-select">
                                <option value="">👥 Tất cả vai trò</option>
                                <option value="<?= ROLE_USER ?>" <?= $role == ROLE_USER ? 'selected' : '' ?>>👤 User</option>
                                <option value="<?= ROLE_SELLER ?>" <?= $role == ROLE_SELLER ? 'selected' : '' ?>>🏪 Seller</option>
                                <option value="<?= ROLE_MODERATOR ?>" <?= $role == ROLE_MODERATOR ? 'selected' : '' ?>>🛡️ Moderator</option>
                                <option value="<?= ROLE_ADMIN ?>" <?= $role == ROLE_ADMIN ? 'selected' : '' ?>>👑 Admin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">
                                <i class="fas fa-shield-alt me-1"></i>
                                Email
                            </label>
                            <select name="email_verified" class="form-select">
                                <option value="">📧 Tất cả</option>
                                <option value="verified" <?= $emailVerified === 'verified' ? 'selected' : '' ?>>✅ Đã xác thực</option>
                                <option value="unverified" <?= $emailVerified === 'unverified' ? 'selected' : '' ?>>⚠️ Chưa xác thực</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-search me-1"></i>
                                Tìm kiếm
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" name="search" class="form-control"
                                       placeholder="Tên, email, username..." value="<?= e($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-search me-2"></i>Tìm kiếm
                            </button>
                            <a href="/admin/users" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users List -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Danh sách người dùng
                        </h5>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-primary fs-6">
                                <?= number_format($total) ?> người dùng
                            </span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="selectAll()">
                                    <i class="fas fa-check-square me-1"></i>Chọn tất cả
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="bulkActivate()">
                                    <i class="fas fa-check me-1"></i>Kích hoạt
                                </button>
                                <button type="button" class="btn btn-outline-warning" onclick="bulkDeactivate()">
                                    <i class="fas fa-ban me-1"></i>Vô hiệu hóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-users fa-4x text-muted opacity-50"></i>
                            </div>
                            <h4 class="text-muted mb-3">Không có người dùng nào</h4>
                            <p class="text-muted mb-4">Không tìm thấy người dùng phù hợp với bộ lọc</p>
                            <a href="/admin/users" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-2"></i>
                                Xem tất cả người dùng
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th width="5%">
                                            <i class="fas fa-hashtag me-1"></i>ID
                                        </th>
                                        <th width="30%">
                                            <i class="fas fa-user me-1"></i>Thông tin
                                        </th>
                                        <th width="12%">
                                            <i class="fas fa-user-tag me-1"></i>Vai trò
                                        </th>
                                        <th width="12%">
                                            <i class="fas fa-shield-alt me-1"></i>Email
                                        </th>
                                        <th width="12%">
                                            <i class="fas fa-toggle-on me-1"></i>Trạng thái
                                        </th>
                                        <th width="12%">
                                            <i class="fas fa-calendar me-1"></i>Ngày tạo
                                        </th>
                                        <th width="17%" class="text-center">
                                            <i class="fas fa-cogs me-1"></i>Thao tác
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $userItem): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-primary">#<?= $userItem['ID'] ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        <?= getUserAvatarHtml(
                                                            $userItem['AnhDaiDien'],
                                                            'avatar-img',
                                                            'Avatar'
                                                        ) ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1 fw-bold text-dark"><?= e($userItem['HoTen']) ?></h6>
                                                        <div class="text-muted small">
                                                            <i class="fas fa-at me-1"></i>
                                                            <?= e($userItem['TenDN']) ?>
                                                        </div>
                                                        <div class="text-muted small">
                                                            <i class="fas fa-envelope me-1"></i>
                                                            <?= e($userItem['Email']) ?>
                                                        </div>
                                                        <?php if (!empty($userItem['SDT'])): ?>
                                                            <div class="text-muted small">
                                                                <i class="fas fa-phone me-1"></i>
                                                                <?= e($userItem['SDT']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $roleClass = match($userItem['VaiTroID']) {
                                                    ROLE_ADMIN => 'bg-danger',
                                                    ROLE_MODERATOR => 'bg-warning',
                                                    ROLE_SELLER => 'bg-info',
                                                    default => 'bg-secondary'
                                                };

                                                $roleIcon = match($userItem['VaiTroID']) {
                                                    ROLE_ADMIN => 'fas fa-crown',
                                                    ROLE_MODERATOR => 'fas fa-shield-alt',
                                                    ROLE_SELLER => 'fas fa-store',
                                                    default => 'fas fa-user'
                                                };
                                                ?>
                                                <span class="badge <?= $roleClass ?> fs-6">
                                                    <i class="<?= $roleIcon ?> me-1"></i>
                                                    <?= e($userItem['TenVaiTro'] ?? 'User') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($userItem['email_verified_at'])): ?>
                                                    <span class="badge bg-success fs-6">
                                                        <i class="fas fa-check-circle me-1"></i>Đã xác thực
                                                    </span>
                                                    <div class="text-muted small mt-1">
                                                        <?= date('d/m/Y H:i', strtotime($userItem['email_verified_at'])) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-warning fs-6">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Chưa xác thực
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($userItem['TrangThai']): ?>
                                                    <span class="badge bg-success fs-6">
                                                        <i class="fas fa-check me-1"></i>Hoạt động
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning fs-6">
                                                        <i class="fas fa-pause me-1"></i>Vô hiệu hóa
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?= date('d/m/Y', strtotime($userItem['NgayTao'])) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= date('H:i', strtotime($userItem['NgayTao'])) ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                                type="button"
                                                                data-bs-toggle="dropdown"
                                                                title="Thao tác với người dùng"
                                                                data-bs-toggle="tooltip">
                                                            <i class="fas fa-cog me-1"></i>Thao tác
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <button class="dropdown-item" onclick="viewUser(<?= $userItem['ID'] ?>)">
                                                                    <i class="fas fa-eye me-2"></i>Xem chi tiết
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button class="dropdown-item" onclick="editUser(<?= $userItem['ID'] ?>)">
                                                                    <i class="fas fa-edit me-2"></i>Chỉnh sửa
                                                                </button>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>

                                                            <?php if ($userItem['TrangThai']): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                                        <input type="hidden" name="action" value="deactivate">
                                                                        <input type="hidden" name="user_id" value="<?= $userItem['ID'] ?>">
                                                                        <button type="submit" class="dropdown-item text-warning"
                                                                                onclick="return confirm('⚠️ Bạn có chắc muốn vô hiệu hóa tài khoản này?')">
                                                                            <i class="fas fa-ban me-2"></i>Vô hiệu hóa
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php else: ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                                        <input type="hidden" name="action" value="activate">
                                                                        <input type="hidden" name="user_id" value="<?= $userItem['ID'] ?>">
                                                                        <button type="submit" class="dropdown-item text-success">
                                                                            <i class="fas fa-check me-2"></i>Kích hoạt
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>

                                                            <li><hr class="dropdown-divider"></li>

                                                            <!-- Email Verification Actions -->
                                                            <?php if (empty($userItem['email_verified_at'])): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                                        <input type="hidden" name="action" value="verify_email">
                                                                        <input type="hidden" name="user_id" value="<?= $userItem['ID'] ?>">
                                                                        <button type="submit" class="dropdown-item text-success"
                                                                                onclick="return confirm('✅ Xác thực email cho người dùng này?')">
                                                                            <i class="fas fa-check-circle me-2"></i>Xác thực email
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php else: ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                                        <input type="hidden" name="action" value="unverify_email">
                                                                        <input type="hidden" name="user_id" value="<?= $userItem['ID'] ?>">
                                                                        <button type="submit" class="dropdown-item text-warning"
                                                                                onclick="return confirm('⚠️ Hủy xác thực email cho người dùng này?')">
                                                                            <i class="fas fa-times-circle me-2"></i>Hủy xác thực email
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>

                                                            <?php if ($userItem['VaiTroID'] != ROLE_SELLER): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                                        <input type="hidden" name="action" value="promote_seller">
                                                                        <input type="hidden" name="user_id" value="<?= $userItem['ID'] ?>">
                                                                        <button type="submit" class="dropdown-item text-info"
                                                                                onclick="return confirm('🏪 Nâng cấp người dùng này thành Seller?')">
                                                                            <i class="fas fa-store me-2"></i>Nâng cấp Seller
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>

                                                            <?php if ($auth->isAdmin() && $userItem['VaiTroID'] != ROLE_MODERATOR): ?>
                                                                <li>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                                        <input type="hidden" name="action" value="promote_moderator">
                                                                        <input type="hidden" name="user_id" value="<?= $userItem['ID'] ?>">
                                                                        <button type="submit" class="dropdown-item text-primary"
                                                                                onclick="return confirm('🛡️ Nâng cấp người dùng này thành Moderator?')">
                                                                            <i class="fas fa-shield-alt me-2"></i>Nâng cấp Moderator
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="User pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&role=<?= urlencode($role) ?>&search=<?= urlencode($search) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&role=<?= urlencode($role) ?>&search=<?= urlencode($search) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&role=<?= urlencode($role) ?>&search=<?= urlencode($search) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/layouts/admin/footer.php'; ?>

<style>
.user-avatar {
    position: relative;
}

.avatar-img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e9ecef;
}

.avatar-placeholder {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    border: 2px solid #e9ecef;
}

.user-avatar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 50%;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.table tbody tr:hover .user-avatar::after {
    opacity: 1;
}

@media (max-width: 768px) {
    .avatar-img, .avatar-placeholder {
        width: 40px;
        height: 40px;
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle dropdown open/close to prevent table row hover conflicts
    const dropdownToggles = document.querySelectorAll('.table .dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        const dropdown = toggle.closest('.dropdown');
        const tableRow = toggle.closest('tr');

        // Listen for dropdown show/hide events
        dropdown.addEventListener('show.bs.dropdown', function() {
            // Add class to prevent hover effects
            tableRow.classList.add('dropdown-open');

            // Close other open dropdowns in the same table
            const otherRows = document.querySelectorAll('.table tbody tr.dropdown-open');
            otherRows.forEach(row => {
                if (row !== tableRow) {
                    const otherDropdown = row.querySelector('.dropdown');
                    if (otherDropdown) {
                        const otherToggle = otherDropdown.querySelector('.dropdown-toggle');
                        if (otherToggle) {
                            const bsDropdown = bootstrap.Dropdown.getInstance(otherToggle);
                            if (bsDropdown) {
                                bsDropdown.hide();
                            }
                        }
                    }
                }
            });
        });

        // Remove manual positioning - let Bootstrap handle it

        dropdown.addEventListener('hide.bs.dropdown', function() {
            // Remove class to restore hover effects
            tableRow.classList.remove('dropdown-open');
        });
    });
});

// User management functions
function viewUser(userId) {
    // Open user detail page
    window.location.href = `/admin/users/info?id=${userId}`;
}

function editUser(userId) {
    // Open user edit page
    window.location.href = `/admin/users/edit?id=${userId}`;
}

function addUser() {
    // Open add user page
    window.location.href = '/admin/users/create';
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });

    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkButtons = document.querySelectorAll('[onclick*="bulk"]');

    bulkButtons.forEach(btn => {
        btn.disabled = checkedBoxes.length === 0;
    });
}

function bulkActivate() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Vui lòng chọn ít nhất một người dùng');
        return;
    }

    if (confirm(`✅ Kích hoạt ${checkedBoxes.length} tài khoản đã chọn?`)) {
        // Implementation for bulk activate
        alert('Chức năng kích hoạt hàng loạt sẽ được phát triển trong phiên bản tiếp theo.');
    }
}

function bulkDeactivate() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Vui lòng chọn ít nhất một người dùng');
        return;
    }

    if (confirm(`⚠️ Vô hiệu hóa ${checkedBoxes.length} tài khoản đã chọn?`)) {
        // Implementation for bulk deactivate
        alert('Chức năng vô hiệu hóa hàng loạt sẽ được phát triển trong phiên bản tiếp theo.');
    }
}

function refreshUsers() {
    window.location.reload();
}

function exportUsers() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Danh sách người dùng - Trọ 365\n";
    csvContent += "Thời gian xuất: " + new Date().toLocaleString('vi-VN') + "\n\n";

    csvContent += "ID,Họ tên,Username,Email,Số điện thoại,Vai trò,Trạng thái,Ngày tạo\n";

    // Get visible users data
    const userRows = document.querySelectorAll('tbody tr');
    userRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            const id = cells[0].textContent.trim().replace('#', '');
            const name = cells[1].querySelector('h6')?.textContent.trim() || '';
            const username = cells[1].querySelector('.text-muted')?.textContent.trim().replace('@', '') || '';
            const email = cells[1].querySelectorAll('.text-muted')[1]?.textContent.trim() || '';
            const phone = cells[1].querySelectorAll('.text-muted')[2]?.textContent.trim() || '';
            const role = cells[2].textContent.trim();
            const status = cells[3].textContent.trim();
            const date = cells[4].textContent.trim();

            csvContent += `"${id}","${name}","${username}","${email}","${phone}","${role}","${status}","${date}"\n`;
        }
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "nguoi-dung-" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Confirm actions
document.querySelectorAll('form[method="POST"]').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        const action = form.querySelector('input[name="action"]')?.value;
        if (action && ['deactivate', 'promote_admin'].includes(action)) {
            // Already handled by onclick confirm
        }
    });
});
</script>
</body>
</html>
