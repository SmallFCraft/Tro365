<?php
/**
 * Admin Users Management Page
 * Tro365 - Website thuê trọ
 */

// Performance optimization includes
require_once __DIR__ . '/../../../includes/performance/optimization.php';

use Tro365\Core\Auth;
use Tro365\Models\User;
use Tro365\Core\Database;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();
$user = new User();
$db = Database::getInstance();

// Force refresh session to get latest role
$auth->updateSession();

// Require admin access
$auth->requireModerator();

$error = '';
$success = '';

// Note: User actions are now handled via AJAX endpoint at /admin/ajax/user-actions.php

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

// Get statistics with caching for better performance
$statsCacheKey = "admin_users_stats";
$stats = cache_get($statsCacheKey);

if ($stats === null) {
    // Optimized single query to get all statistics at once
    $statsResult = $db->selectOne("
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN TrangThai = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN TrangThai = 0 THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN VaiTroID = :seller_role THEN 1 ELSE 0 END) as sellers,
            SUM(CASE WHEN VaiTroID = :admin_role THEN 1 ELSE 0 END) as admins,
            SUM(CASE WHEN VaiTroID = :moderator_role THEN 1 ELSE 0 END) as moderators
        FROM KhachHang
    ", [
        'seller_role' => ROLE_SELLER,
        'admin_role' => ROLE_ADMIN,
        'moderator_role' => ROLE_MODERATOR
    ]);

    $stats = [
        'total' => (int)($statsResult['total'] ?? 0),
        'active' => (int)($statsResult['active'] ?? 0),
        'pending' => (int)($statsResult['pending'] ?? 0),
        'sellers' => (int)($statsResult['sellers'] ?? 0),
        'admins' => (int)($statsResult['admins'] ?? 0),
        'moderators' => (int)($statsResult['moderators'] ?? 0)
    ];

    // Cache for 5 minutes
    cache_set($statsCacheKey, $stats, 300);
}

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
            <div class="card users-list-card">
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
                        <div class="users-table-container">
                            <div class="table-responsive-custom">
                                <table class="table mb-0 users-table">
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
                                        <tr data-user-id="<?= $userItem['ID'] ?>" class="user-row">
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
                                                <span class="badge <?= $roleClass ?> fs-6 role-badge">
                                                    <i class="<?= $roleIcon ?> me-1"></i>
                                                    <?= e($userItem['TenVaiTro'] ?? 'User') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($userItem['email_verified_at'])): ?>
                                                    <span class="badge bg-success fs-6 email-badge">
                                                        <i class="fas fa-check-circle me-1"></i>Đã xác thực
                                                    </span>
                                                    <div class="text-muted small mt-1">
                                                        <?= date('d/m/Y H:i', strtotime($userItem['email_verified_at'])) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-warning fs-6 email-badge">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Chưa xác thực
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($userItem['TrangThai']): ?>
                                                    <span class="badge bg-success fs-6 status-badge">
                                                        <i class="fas fa-check me-1"></i>Hoạt động
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning fs-6 status-badge">
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
                                                    <div class="dropdown users-action-dropdown">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                                type="button"
                                                                data-bs-toggle="dropdown"
                                                                aria-expanded="false"
                                                                title="Thao tác với người dùng"
                                                                data-bs-toggle="tooltip">
                                                            <i class="fas fa-cog me-1"></i>Thao tác
                                                        </button>
                                                        <ul class="dropdown-menu users-actions-menu">
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
                                                                    <button type="button" class="dropdown-item text-warning"
                                                                            onclick="userAction('deactivate', <?= $userItem['ID'] ?>, '⚠️ Bạn có chắc muốn vô hiệu hóa tài khoản này?')">
                                                                        <i class="fas fa-ban me-2"></i>Vô hiệu hóa
                                                                    </button>
                                                                </li>
                                                            <?php else: ?>
                                                                <li>
                                                                    <button type="button" class="dropdown-item text-success"
                                                                            onclick="userAction('activate', <?= $userItem['ID'] ?>, '✅ Kích hoạt tài khoản này?')">
                                                                        <i class="fas fa-check me-2"></i>Kích hoạt
                                                                    </button>
                                                                </li>
                                                            <?php endif; ?>

                                                            <li><hr class="dropdown-divider"></li>

                                                            <!-- Email Verification Actions -->
                                                            <?php if (empty($userItem['email_verified_at'])): ?>
                                                                <li>
                                                                    <button type="button" class="dropdown-item text-success"
                                                                            onclick="userAction('verify_email', <?= $userItem['ID'] ?>, '✅ Xác thực email cho người dùng này?')">
                                                                        <i class="fas fa-check-circle me-2"></i>Xác thực email
                                                                    </button>
                                                                </li>
                                                            <?php else: ?>
                                                                <li>
                                                                    <button type="button" class="dropdown-item text-warning"
                                                                            onclick="userAction('unverify_email', <?= $userItem['ID'] ?>, '⚠️ Hủy xác thực email cho người dùng này?')">
                                                                        <i class="fas fa-times-circle me-2"></i>Hủy xác thực email
                                                                    </button>
                                                                </li>
                                                            <?php endif; ?>

                                                                <li class="dropdown-submenu">
                                                                    <a class="dropdown-item" href="#" onclick="openRoleModal(<?= $userItem['ID'] ?>, <?= (int)$userItem['VaiTroID'] ?>); return false;">
                                                                        <i class="fas fa-user-shield me-2"></i>Quản lý vai trò
                                                                    </a>
                                                                </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
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

<!-- Role Management Modal - Responsive Design -->
<div class="modal fade" id="roleModal" tabindex="-1" style="z-index: 1060 !important;">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content role-modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-shield me-2"></i>Quản lý vai trò</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="roleUserId" value="">
        <div class="mb-3">
          <label class="form-label">Chọn vai trò</label>
          <select id="roleSelect" class="form-select">
            <option value="<?= ROLE_USER ?>">👤 Người dùng</option>
            <option value="<?= ROLE_SELLER ?>">🏪 Người cho thuê</option>
            <option value="<?= ROLE_SUPPORTER ?>">🧰 Nhân viên hỗ trợ</option>
            <option value="<?= ROLE_MODERATOR ?>">🛡️ Kiểm duyệt viên</option>
            <option value="<?= ROLE_ADMIN ?>">👑 Quản trị viên</option>
          </select>
        </div>
        <div class="alert alert-warning small">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Chỉ Admin mới có quyền thay đổi vai trò. Thao tác này sẽ được ghi lại log hoạt động.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
        <button type="button" class="btn btn-primary" onclick="submitRoleChange()">
            <i class="fas fa-save me-1"></i>Lưu thay đổi
        </button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../../includes/layouts/admin/footer.php'; ?>

<style>
/* ===== ADMIN USERS TABLE RESPONSIVE & DROPDOWN FIXES ===== */

/* Custom responsive table container that doesn't clip dropdowns */
.table-responsive-custom {
    width: 100% !important;
    overflow-x: auto !important;
    overflow-y: visible !important;
    -webkit-overflow-scrolling: touch;
    border-radius: 12px;
    position: relative !important;
    /* Force override any conflicting styles */
    display: block !important;
    scrollbar-width: thin;
}

/* Ensure table containers allow dropdown overflow */
.users-list-card,
.users-list-card .card-body,
.users-table-container {
    overflow: visible !important;
    position: relative;
}

/* Table specific overflow settings */
.users-table,
.users-table tbody,
.users-table tr,
.users-table td {
    overflow: visible !important;
    position: relative;
}

/* Enhanced dropdown container with proper z-index hierarchy */
.users-action-dropdown {
    position: relative;
    z-index: 1000;
}

/* Critical z-index for dropdown menu - highest priority */
.users-actions-menu {
    z-index: 10100 !important;
    position: absolute !important;
    transform: none !important;
    will-change: auto !important;
    pointer-events: auto !important;
}

/* Enhanced dropdown menu with improved positioning and visibility */
.users-list-card .dropdown-menu {
    z-index: 10100 !important;
    position: absolute !important;
    min-width: 220px;
    padding: 0.5rem;
    border: none;
    border-radius: 12px;
    box-shadow: 0 14px 40px rgba(0,0,0,0.16);
    background: #ffffffcc;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    animation: adminDropdownIn 160ms cubic-bezier(.2,.7,.3,1) both;
    margin-top: 0.25rem;
    pointer-events: auto !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Dropdown animation */
@keyframes adminDropdownIn {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to   {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Modern dropdown item styling */
.users-list-card .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.6rem 0.9rem;
    border-radius: 8px;
    font-weight: 500;
    color: #2d3748;
    transition: transform 120ms ease, background 120ms ease, color 120ms ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
}

.users-list-card .dropdown-item i {
    width: 1.1rem;
    text-align: center;
    color: #667085;
}

.users-list-card .dropdown-item:hover {
    background: linear-gradient(135deg, rgba(102,126,234,.12), rgba(118,75,162,.12));
    transform: translateX(4px);
    color: #1f2937;
}

.users-list-card .dropdown-item:hover i {
    color: #4f46e5;
}

.users-list-card .dropdown-item.text-danger {
    color: #dc3545;
}

.users-list-card .dropdown-item.text-danger:hover {
    background: rgba(220,53,69,0.12);
    color: #b02a37;
}

.users-list-card .dropdown-item.text-warning {
    color: #f59e0b;
}

.users-list-card .dropdown-item.text-warning:hover {
    background: rgba(245,158,11,0.12);
    color: #b45309;
}

.users-list-card .dropdown-item.text-success {
    color: #198754;
}

.users-list-card .dropdown-item.text-success:hover {
    background: rgba(25,135,84,0.12);
    color: #146c43;
}

.users-list-card .dropdown-item.text-info {
    color: #0dcaf0;
}

.users-list-card .dropdown-item.text-info:hover {
    background: rgba(13,202,240,0.12);
    color: #087990;
}

.users-list-card .dropdown-item.text-primary {
    color: #0d6efd;
}

.users-list-card .dropdown-item.text-primary:hover {
    background: rgba(13,110,253,0.12);
    color: #084298;
}

.users-list-card .dropdown-divider {
    margin: 0.35rem 0.25rem;
    border-color: rgba(0,0,0,0.08);
}

/* Enhanced table row hover state management with dropdown priority */
.users-table tbody tr {
    transition: all 0.3s ease;
    position: relative;
    z-index: 1;
}

.users-table tbody tr:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    /* Avoid transform to prevent stacking context issues over dropdowns */
    transform: none;
    z-index: 2;
}

/* Critical: Dropdown open state must have highest z-index */
.users-table tbody tr.dropdown-open {
    /* keep modest z-index; the menu will have highest */
    z-index: 10 !important;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
    position: relative !important;
}

/* Prevent hover effects when dropdown is open */
.users-table tbody tr.dropdown-open:hover {
    transform: none !important;
    z-index: 10000 !important;
}

/* Prevent table overflow issues */
.users-table td {
    position: relative;
    /* keep default stacking low so dropdown menu can overlay previous rows */
    z-index: auto;
}

/* Action column specific styling with enhanced z-index */
.users-table td:last-child {
    position: relative;
    /* rely on dropdown menu z-index instead of cell z-index */
    z-index: auto;
}

/* When dropdown is open, ensure action column has highest priority */
.users-table tbody tr.dropdown-open td:last-child {
    z-index: 5 !important;
}

/* Enhanced responsive dropdown fixes */
@media (max-width: 768px) {
    .table-responsive-custom {
        border-radius: 8px;
        overflow-x: auto !important;
    }

    /* Header layout stacks vertically on mobile */
    .users-list-card .card-header > .d-flex.justify-content-between {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    /* Right-side controls take full width on a new row */
    .users-list-card .card-header > .d-flex.justify-content-between > .d-flex {
        width: 100%;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .users-list-card .card-header .btn-group {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .users-list-card .card-header .btn-group .btn {
        flex: 1 1 100%; /* stack buttons full width to avoid overlap */
        min-width: 0;
    }

    .users-actions-menu {
        right: 0 !important;
        left: auto !important;
        min-width: 200px;
        z-index: 10150 !important;
    }

    .users-list-card .dropdown-menu {
        min-width: 200px;
        max-width: calc(100vw - 2rem);
        z-index: 10150 !important;
    }

    .users-list-card .dropdown-item {
        padding: 0.5rem 0.7rem;
        font-size: 0.875rem;
    }

    /* Keep table inside card on mobile */
    .users-table-container {
        margin: 0;
        padding: 0;
    }
    .users-table {
        min-width: 920px; /* force horizontal scroll */
    }
}

/* Ensure dropdown button has proper styling */
.users-action-dropdown .dropdown-toggle {
    border: 1px solid #6c757d;
    background: white;
    color: #6c757d;
    transition: all 0.2s ease;
}

.users-action-dropdown .dropdown-toggle:hover {
    background: #f8f9fa;
    border-color: #495057;
    color: #495057;
}

.users-action-dropdown .dropdown-toggle:focus {
    box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
}

/* When any dropdown is open in the users table, suppress ALL hover effects */
.users-table.has-open-dropdown tbody tr:hover {
    transform: none !important;
    background: transparent !important;
    box-shadow: none !important;
    z-index: auto !important;
}

/* Ensure dropdown menu always stays on top when open */
.users-table tbody tr.dropdown-open .dropdown-menu {
    z-index: 10100 !important;
    position: absolute !important;
}

/* Fix modal z-index issues */
#roleModal {
    z-index: 1060 !important;
}

#roleModal .modal-dialog {
    z-index: 1061 !important;
    position: relative !important;
}

#roleModal .modal-content {
    z-index: 1062 !important;
    position: relative !important;
}

/* Ensure modal backdrop doesn't interfere with modal content */
.modal-backdrop {
    z-index: 1040 !important;
}

#roleModal ~ .modal-backdrop {
    z-index: 1040 !important;
}

/* ==========================================================================
   ROLE MODAL RESPONSIVE DESIGN
   ========================================================================== */

/* Role Modal - Mobile First Design */
.role-modal-content {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    margin: 1rem;
}

[data-theme="dark"] .role-modal-content {
    background: rgba(30, 30, 30, 0.95);
    border-color: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

/* Modal Header */
.role-modal-content .modal-header {
    background: rgba(0, 123, 255, 0.1);
    border-bottom: 1px solid rgba(0, 123, 255, 0.2);
    border-radius: 16px 16px 0 0;
    padding: 1.25rem;
}

.role-modal-content .modal-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #007bff;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

[data-theme="dark"] .role-modal-content .modal-title {
    color: #4dabf7;
}

/* Modal Body */
.role-modal-content .modal-body {
    padding: 1.5rem;
}

.role-modal-content .form-group {
    margin-bottom: 1.5rem;
}

.role-modal-content .form-label {
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: #495057;
    font-size: 0.95rem;
}

[data-theme="dark"] .role-modal-content .form-label {
    color: #e9ecef;
}

.role-modal-content .form-select {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(0, 123, 255, 0.2);
    border-radius: 12px;
    padding: 0.875rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    min-height: 48px; /* Touch-friendly */
}

.role-modal-content .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.15);
    background: rgba(255, 255, 255, 0.95);
}

[data-theme="dark"] .role-modal-content .form-select {
    background: rgba(40, 40, 40, 0.9);
    border-color: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}

/* Warning Text */
.role-modal-content .text-warning {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid rgba(255, 193, 7, 0.3);
    border-radius: 8px;
    padding: 0.75rem;
    font-size: 0.875rem;
    line-height: 1.4;
}

/* Modal Footer */
.role-modal-content .modal-footer {
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 0 0 16px 16px;
    padding: 1.25rem;
    gap: 0.75rem;
}

[data-theme="dark"] .role-modal-content .modal-footer {
    border-top-color: rgba(255, 255, 255, 0.1);
}

/* Modal Buttons */
.role-modal-content .btn {
    border-radius: 10px;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    min-height: 44px; /* Touch-friendly */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.role-modal-content .btn-secondary {
    background: rgba(108, 117, 125, 0.1);
    border: 1px solid rgba(108, 117, 125, 0.3);
    color: #6c757d;
}

.role-modal-content .btn-secondary:hover {
    background: rgba(108, 117, 125, 0.2);
    transform: translateY(-1px);
}

.role-modal-content .btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: none;
    color: white;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
}

.role-modal-content .btn-primary:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
}

/* Mobile Responsive Adjustments */
@media (max-width: 576px) {
    #roleModal .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }

    .role-modal-content {
        margin: 0;
        border-radius: 12px;
    }

    .role-modal-content .modal-header,
    .role-modal-content .modal-body,
    .role-modal-content .modal-footer {
        padding: 1rem;
    }

    .role-modal-content .modal-title {
        font-size: 1rem;
    }

    .role-modal-content .modal-footer {
        flex-direction: column;
    }

    .role-modal-content .modal-footer .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }

    .role-modal-content .modal-footer .btn:last-child {
        margin-bottom: 0;
    }
}

/* Tablet Responsive */
@media (min-width: 577px) and (max-width: 768px) {
    #roleModal .modal-dialog {
        max-width: 500px;
        margin: 1rem auto;
    }
}

/* Desktop Responsive */
@media (min-width: 769px) {
    #roleModal .modal-dialog {
        max-width: 540px;
        margin: 2rem auto;
    }

    .role-modal-content .modal-header,
    .role-modal-content .modal-body,
    .role-modal-content .modal-footer {
        padding: 1.5rem;
    }
}

/* Completely disable hover effects on all rows except the one with open dropdown */
.users-table.has-open-dropdown tbody tr:not(.dropdown-open):hover {
    background: transparent !important;
    transform: none !important;
    z-index: auto !important;
    box-shadow: none !important;
}

/* Force the dropdown-open row to stay above everything */
.users-table tbody tr.dropdown-open {
    z-index: 10000 !important;
    position: relative !important;
}

/* User avatar styles */
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

    // Enhanced dropdown management for users table
    const userDropdownToggles = document.querySelectorAll('.users-action-dropdown .dropdown-toggle');

    userDropdownToggles.forEach(toggle => {
        const dropdown = toggle.closest('.users-action-dropdown');
        const tableRow = toggle.closest('tr');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');

        // Listen for dropdown show event with enhanced positioning
        dropdown.addEventListener('show.bs.dropdown', function(e) {
            console.log('Dropdown opening for user row');

            // Add class to table row to prevent hover conflicts
            tableRow.classList.add('dropdown-open');
            // Also add a class to table to indicate an active dropdown (suppresses hover scaling site-wide)
            const usersTable = tableRow.closest('.users-table');
            if (usersTable) usersTable.classList.add('has-open-dropdown');

            // Close any other open dropdowns in the table
            const otherOpenRows = document.querySelectorAll('.users-table tbody tr.dropdown-open');
            otherOpenRows.forEach(row => {
                if (row !== tableRow) {
                    row.classList.remove('dropdown-open');
                    const otherDropdown = row.querySelector('.users-action-dropdown');
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

            // Enhanced dropdown positioning with higher z-index
            setTimeout(() => {
                if (dropdownMenu) {
                    dropdownMenu.style.zIndex = '10100';
                    dropdownMenu.style.position = 'absolute';
                    dropdownMenu.style.pointerEvents = 'auto';
                    dropdownMenu.style.visibility = 'visible';
                    dropdownMenu.style.opacity = '1';

                    // Check if dropdown would go off-screen and adjust
                    const rect = dropdownMenu.getBoundingClientRect();
                    const viewportWidth = window.innerWidth;

                    if (rect.right > viewportWidth) {
                        dropdownMenu.classList.add('dropdown-menu-end');
                    }
                }
            }, 10);
        });

        // Listen for dropdown shown event with enhanced z-index enforcement
        dropdown.addEventListener('shown.bs.dropdown', function() {
            console.log('Dropdown fully opened');
            // Force highest z-index after Bootstrap positioning
            if (dropdownMenu) {
                dropdownMenu.style.zIndex = '10150';
                dropdownMenu.style.pointerEvents = 'auto';

                // Ensure parent elements don't interfere
                tableRow.style.zIndex = '10';
                dropdown.style.zIndex = '11';
            }
        });

        // Listen for dropdown hide event
        dropdown.addEventListener('hide.bs.dropdown', function() {
            console.log('Dropdown closing');
            // Remove class to restore normal hover effects
            tableRow.classList.remove('dropdown-open');
            const usersTable = tableRow.closest('.users-table');
            if (usersTable) usersTable.classList.remove('has-open-dropdown');
        });

        // Enhanced click outside handler with improved usability
        const clickOutsideHandler = function(e) {
            // Don't close if clicking on the dropdown button itself
            if (toggle.contains(e.target)) {
                return;
            }

            // Don't close if clicking inside the dropdown menu
            if (dropdownMenu && dropdownMenu.contains(e.target)) {
                return;
            }

            // Don't close if clicking on any dropdown-related element
            if (e.target.closest('.users-actions-menu') || e.target.closest('.users-action-dropdown')) {
                return;
            }

            // Only close if clicking completely outside
            if (!dropdown.contains(e.target)) {
                const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
                if (bsDropdown && dropdown.classList.contains('show')) {
                    bsDropdown.hide();
                }
            }
        };

        // Add click outside handler with delay to prevent immediate closure
        setTimeout(() => {
            document.addEventListener('click', clickOutsideHandler);
        }, 100);

        // Prevent dropdown from closing when clicking inside menu
        if (dropdownMenu) {
            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
            });

            // Prevent dropdown items from closing the menu immediately
            const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
            dropdownItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    // Allow the action to proceed but prevent immediate closure
                    setTimeout(() => {
                        const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
                        if (bsDropdown) {
                            bsDropdown.hide();
                        }
                    }, 150);
                });
            });
        }
    });

    // Prevent dropdown from closing when clicking inside dropdown menu
    document.querySelectorAll('.users-actions-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            // Allow form submissions and buttons to work normally
            if (e.target.tagName === 'BUTTON' && e.target.type === 'submit') {
                return true;
            }

            // For non-form elements, stop propagation to prevent dropdown close
            if (!e.target.closest('form')) {
                e.stopPropagation();
            }
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
    // TODO: Implement bulk activate functionality
}

// Role Management JS - Define as global functions
window.openRoleModal = function(userId, currentRole){
  try {
    const roleUserIdEl = document.getElementById('roleUserId');
    const roleSelectEl = document.getElementById('roleSelect');
    const roleModalEl = document.getElementById('roleModal');

    if (!roleUserIdEl || !roleSelectEl || !roleModalEl) {
      console.error('Role modal elements not found:', {
        roleUserId: !!roleUserIdEl,
        roleSelect: !!roleSelectEl,
        roleModal: !!roleModalEl
      });
      showToast('Không thể mở modal quản lý vai trò', 'error');
      return;
    }

    roleUserIdEl.value = userId;
    roleSelectEl.value = String(currentRole);

    // Fix z-index issues by setting modal z-index higher than backdrop
    roleModalEl.style.zIndex = '1060';

    const modal = new bootstrap.Modal(roleModalEl, {
      backdrop: false,
      keyboard: true,
      focus: true
    });

    // Ensure modal appears above backdrop
    modal.show();

    // Additional fix: Set z-index after modal is shown
    setTimeout(() => {
      roleModalEl.style.zIndex = '1060';
      const modalDialog = roleModalEl.querySelector('.modal-dialog');
      if (modalDialog) modalDialog.style.zIndex = '1061';
    }, 100);

  } catch (error) {
    console.error('Error opening role modal:', error);
    showToast('Có lỗi xảy ra khi mở modal', 'error');
  }
}

function submitRoleChange(){
  const userId = parseInt(document.getElementById('roleUserId').value,10);
  const newRole = parseInt(document.getElementById('roleSelect').value,10);
  if(!userId || !newRole){
    showToast('Dữ liệu không hợp lệ', 'error');
    return;
  }
  const btn = event.target.closest('button');
  const original = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...';
  btn.disabled = true;

  fetch('/pages/admin/ajax/user-actions.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
      action: 'change_role',
      user_id: userId,
      new_role: newRole,
      csrf_token: '<?= csrf_token() ?>'
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      showToast(data.message || 'Cập nhật vai trò thành công', 'success');
      // Update badge UI same as userAction success handler
      const row = document.querySelector(`tr[data-user-id="${userId}"]`);
      if (row && data.updated_data) {
        const badge = row.querySelector('.role-badge');
        if (badge) {
          const ud = data.updated_data;
          badge.className = `badge bg-${ud.role_class} fs-6 role-badge`;
          let icon = 'fas fa-user';
          if (ud.role === <?= ROLE_ADMIN ?>) icon = 'fas fa-crown';
          else if (ud.role === <?= ROLE_MODERATOR ?>) icon = 'fas fa-shield-alt';
          else if (ud.role === <?= ROLE_SELLER ?>) icon = 'fas fa-store';
          else if (ud.role === <?= ROLE_SUPPORTER ?>) icon = 'fas fa-headset';
          badge.innerHTML = `<i class="${icon} me-1"></i>${ud.role_text}`;
        }
      }
      // Close modal
      const modalEl = document.getElementById('roleModal');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
    } else {
      showToast(data.message || 'Có lỗi xảy ra', 'error');
    }
  })
  .catch(() => showToast('Không thể cập nhật vai trò', 'error'))
  .finally(() => { btn.innerHTML = original; btn.disabled = false; });
}

function bulkActivateUsers() {
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
    // Soft refresh: re-fetch current page users via AJAX and re-render table
    const params = new URLSearchParams(window.location.search);
    fetch(`/admin/users?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(res => res.text())
        .then(html => {
            // Extract only users table card from response
            const temp = document.createElement('div');
            temp.innerHTML = html;
            const newCard = temp.querySelector('.users-list-card');
            const oldCard = document.querySelector('.users-list-card');
            if (newCard && oldCard) {
                oldCard.replaceWith(newCard);
                showToast('Đã làm mới danh sách người dùng', 'info');
            } else {
                window.location.reload(); // fallback
            }
        })
        .catch(() => window.location.reload());
}

// AJAX User Action Handler - Define as global function
window.userAction = function(action, userId, confirmMessage) {
    if (confirmMessage && !confirm(confirmMessage)) {
        return;
    }

    // Show loading state
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
    button.disabled = true;

    fetch('/pages/admin/ajax/user-actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: action,
            user_id: userId,
            csrf_token: '<?= csrf_token() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success toast
            showToast(data.message, 'success');

            // Update UI in real-time
            updateUserRowUI(userId, data.updated_data);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Không thể thực hiện thao tác');
    })
    .finally(() => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Update user row UI in real-time
function updateUserRowUI(userId, updatedData) {
    const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!userRow) return;

    // Update status badge
    if (updatedData.status !== undefined) {
        const statusBadge = userRow.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.className = `badge bg-${updatedData.status_class} fs-6 status-badge`;
            const statusIcon = updatedData.status === 1 ? 'fas fa-check' : 'fas fa-pause';
            statusBadge.innerHTML = `<i class="${statusIcon} me-1"></i>${updatedData.status_text}`;
        }
    }

    // Update role badge
    if (updatedData.role !== undefined) {
        const roleBadge = userRow.querySelector('.role-badge');
        if (roleBadge) {
            roleBadge.className = `badge bg-${updatedData.role_class} fs-6 role-badge`;
            let roleIcon = 'fas fa-user';
            if (updatedData.role === <?= ROLE_ADMIN ?>) roleIcon = 'fas fa-crown';
            else if (updatedData.role === <?= ROLE_MODERATOR ?>) roleIcon = 'fas fa-shield-alt';
            else if (updatedData.role === <?= ROLE_SELLER ?>) roleIcon = 'fas fa-store';
            roleBadge.innerHTML = `<i class="${roleIcon} me-1"></i>${updatedData.role_text}`;
        }
    }

    // Update email verification status
    if (updatedData.email_verified !== undefined) {
        const emailBadge = userRow.querySelector('.email-badge');
        if (emailBadge) {
            emailBadge.className = `badge bg-${updatedData.email_class} fs-6 email-badge`;
            const emailIcon = updatedData.email_verified ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
            emailBadge.innerHTML = `<i class="${emailIcon} me-1"></i>${updatedData.email_status}`;
        }
    }

    // Update dropdown menu options
    // Dynamically update dropdown options without reloading
    const dropdownMenu = userRow.querySelector('.users-actions-menu');
    if (dropdownMenu && updatedData) {
        // Update Activate/Deactivate option
        const deactivateBtn = dropdownMenu.querySelector('button[onclick^="userAction(\'deactivate\'"]');
        const activateBtn = dropdownMenu.querySelector('button[onclick^="userAction(\'activate\'"]');
        if (updatedData.status !== undefined) {
            if (updatedData.status === 1) {
                // Ensure deactivate option exists and activate is removed
                if (!deactivateBtn) {
                    const li = document.createElement('li');
                    li.innerHTML = '<button type="button" class="dropdown-item text-warning" onclick="userAction(\'deactivate\','+userId+',\'⚠️ Bạn có chắc muốn vô hiệu hóa tài khoản này?\')"><i class="fas fa-ban me-2"></i>Vô hiệu hóa</button>';
                    // Insert before divider after edit
                    const dividers = dropdownMenu.querySelectorAll('.dropdown-divider');
                    if (dividers.length) {
                        dropdownMenu.insertBefore(li, dividers[dividers.length-1].parentElement);
                    } else {
                        dropdownMenu.appendChild(li);
                    }
                }
                if (activateBtn) activateBtn.closest('li').remove();
            } else {
                // Ensure activate option exists and deactivate is removed
                if (!activateBtn) {
                    const li = document.createElement('li');
                    li.innerHTML = '<button type="button" class="dropdown-item text-success" onclick="userAction(\'activate\','+userId+',\'✅ Kích hoạt tài khoản này?\')"><i class="fas fa-check me-2"></i>Kích hoạt</button>';
                    const dividers = dropdownMenu.querySelectorAll('.dropdown-divider');
                    if (dividers.length) {
                        dropdownMenu.insertBefore(li, dividers[dividers.length-1].parentElement);
                    } else {
                        dropdownMenu.appendChild(li);
                    }
                }
                if (deactivateBtn) deactivateBtn.closest('li').remove();
            }
        }

        // Update email verify/unverify
        const verifyBtn = dropdownMenu.querySelector('button[onclick^="userAction(\'verify_email\'"]');
        const unverifyBtn = dropdownMenu.querySelector('button[onclick^="userAction(\'unverify_email\'"]');
        if (updatedData.email_verified !== undefined) {
            if (updatedData.email_verified) {
                if (!unverifyBtn) {
                    const li = document.createElement('li');
                    li.innerHTML = '<button type="button" class="dropdown-item text-warning" onclick="userAction(\'unverify_email\','+userId+',\'⚠️ Hủy xác thực email cho người dùng này?\')"><i class="fas fa-times-circle me-2"></i>Hủy xác thực email</button>';
                    dropdownMenu.appendChild(li);
                }
                if (verifyBtn) verifyBtn.closest('li').remove();
            } else {
                if (!verifyBtn) {
                    const li = document.createElement('li');
                    li.innerHTML = '<button type="button" class="dropdown-item text-success" onclick="userAction(\'verify_email\','+userId+',\'✅ Xác thực email cho người dùng này?\')"><i class="fas fa-check-circle me-2"></i>Xác thực email</button>';
                    dropdownMenu.appendChild(li);
                }
                if (unverifyBtn) unverifyBtn.closest('li').remove();
            }
        }

        // Update role actions: no-op (role management via modal)
        if (updatedData.role !== undefined) {
            // Nothing to adjust here; dropdown content for role management is static
        }
    }
}

function exportUsers() {
    // Create CSV content with proper UTF-8 BOM for Excel compatibility
    let csvContent = "\uFEFF"; // UTF-8 BOM
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

    // Use Blob API for proper UTF-8 encoding
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "nguoi-dung-" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url); // Clean up
}

// Initialize toast notifications if not already available
if (typeof showToast !== 'function') {
    function showToast(type, message) {
        // Fallback toast implementation
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', alertHtml);

        // Auto remove after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert:last-of-type');
            if (alert) alert.remove();
        }, 5000);
    }
}
</script>

