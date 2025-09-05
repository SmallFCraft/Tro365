<?php
/**
 * Admin Posts Management
 * Tro365 - Website thuê trọ
 */

// Performance optimization includes
require_once __DIR__ . '/../../../includes/performance/optimization.php';

use Tro365\Core\Auth;
use Tro365\Models\Post;
use Tro365\Core\Database;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();
$db = Database::getInstance();

// Force refresh session to get latest role
$auth->updateSession();

// Require admin access
$auth->requireModerator();

$error = '';
$success = '';

// Initialize variables with safe defaults to prevent undefined variable warnings
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$total = 0;
$totalPages = 0;
$posts = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }
        
        $action = $_POST['action'] ?? '';
        $postId = (int)($_POST['post_id'] ?? 0);
        
        if (!$postId) {
            throw new Exception('ID bài đăng không hợp lệ');
        }
        
        switch ($action) {
            case 'approve':
                $db->update('BaiDang', ['TrangThai' => POST_STATUS_APPROVED], 'ID = :id', ['id' => $postId]);
                $success = 'Duyệt bài đăng thành công!';
                break;
                
            case 'reject':
                $db->update('BaiDang', ['TrangThai' => POST_STATUS_REJECTED], 'ID = :id', ['id' => $postId]);
                $success = 'Từ chối bài đăng thành công!';
                break;
                
            case 'toggle_visibility':
                // Get current status
                $post = $db->selectOne('SELECT TrangThai FROM BaiDang WHERE ID = :id', ['id' => $postId]);
                if (!$post) {
                    throw new Exception('Không tìm thấy bài đăng');
                }
                
                $newStatus = $post['TrangThai'] == POST_STATUS_APPROVED ? POST_STATUS_HIDDEN : POST_STATUS_APPROVED;
                $db->update('BaiDang', ['TrangThai' => $newStatus], 'ID = :id', ['id' => $postId]);
                
                $statusText = $newStatus == POST_STATUS_HIDDEN ? 'Ẩn' : 'Hiển thị';
                $success = $statusText . ' bài đăng thành công!';
                break;
                
            case 'delete':
                // Enhanced deletion with automatic image cleanup
                try {
                    // Get post data before deletion to access image paths
                    $postData = $db->selectOne('SELECT AnhDaiDien FROM BaiDang WHERE ID = :id', ['id' => $postId]);

                    if ($postData) {
                        // Initialize Post model for image cleanup
                        $postModel = new Post();

                        // Delete all additional images (HinhAnhBaiDang table + files)
                        $postModel->deletePostImages($postId);

                        // Delete main image and all its versions (original, WebP, thumbnails)
                        if (!empty($postData['AnhDaiDien'])) {
                            deleteImageWithAllVersions($postData['AnhDaiDien']);
                        }
                    }

                    // Finally delete the post record from database
                    $db->delete('BaiDang', 'ID = :id', ['id' => $postId]);
                    $success = 'Xóa bài đăng và tất cả hình ảnh thành công!';

                } catch (Exception $deleteError) {
                    // If image cleanup fails, still try to delete the post record
                    error_log("Image cleanup failed for post $postId: " . $deleteError->getMessage());
                    $db->delete('BaiDang', 'ID = :id', ['id' => $postId]);
                    $success = 'Xóa bài đăng thành công! (Một số hình ảnh có thể chưa được xóa)';
                }
                break;

            default:
                throw new Exception('Hành động không hợp lệ');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get filters and build query after handling POST requests
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;

// Build query conditions
$conditions = [];
$params = [];

if ($status !== '' && is_numeric($status)) {
    $conditions[] = "bd.TrangThai = :status";
    $params['status'] = (int)$status;
}

if ($search) {
    $conditions[] = "(bd.TieuDe LIKE :search1 OR bd.DiaChi LIKE :search2 OR kh.HoTen LIKE :search3)";
    $params['search1'] = '%' . $search . '%';
    $params['search2'] = '%' . $search . '%';
    $params['search3'] = '%' . $search . '%';
}

$whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get posts with pagination
$offset = ($page - 1) * $limit;
$sql = "SELECT bd.*, kh.HoTen as NguoiDang
        FROM BaiDang bd
        LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
        $whereClause
        ORDER BY bd.NgayTao DESC
        LIMIT :limit OFFSET :offset";

$params['limit'] = $limit;
$params['offset'] = $offset;

try {
    $posts = $db->select($sql, $params);
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM BaiDang bd LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID $whereClause";
    $countParams = array_diff_key($params, ['limit' => '', 'offset' => '']);
    $totalResult = $db->selectOne($countSql, $countParams);
    $total = $totalResult['total'] ?? 0;
    $totalPages = ceil($total / $limit);
    
    // Get statistics with caching for better performance
    $statsCacheKey = "admin_posts_stats";
    $stats = cache_get($statsCacheKey);

    if ($stats === null) {
        // Optimized single query to get all statistics at once
        $statsResult = $db->selectOne("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN TrangThai = :pending THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN TrangThai = :approved THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN TrangThai = :rejected THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN TrangThai = :hidden THEN 1 ELSE 0 END) as hidden
            FROM BaiDang
        ", [
            'pending' => POST_STATUS_PENDING,
            'approved' => POST_STATUS_APPROVED,
            'rejected' => POST_STATUS_REJECTED,
            'hidden' => POST_STATUS_HIDDEN
        ]);

        $stats = [
            'total' => (int)($statsResult['total'] ?? 0),
            'pending' => (int)($statsResult['pending'] ?? 0),
            'approved' => (int)($statsResult['approved'] ?? 0),
            'rejected' => (int)($statsResult['rejected'] ?? 0),
            'hidden' => (int)($statsResult['hidden'] ?? 0)
        ];

        // Cache for 5 minutes
        cache_set($statsCacheKey, $stats, 300);
    }
} catch (Exception $e) {
    // Log error and use default values
    error_log("Admin posts query error: " . $e->getMessage());
    $posts = [];
    $total = 0;
    $totalPages = 0;
    $stats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
}

$pageTitle = 'Quản lý bài đăng';
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
                        <i class="fas fa-clipboard-check me-1"></i>
                        Duyệt bài đăng
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-clipboard-check me-3"></i>
                            Duyệt bài đăng
                        </h1>
                        <p class="text-muted mb-0">Quản lý và duyệt các bài đăng trong hệ thống</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success" onclick="exportPosts()">
                            <i class="fas fa-download me-2"></i>Xuất danh sách
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="refreshPosts()">
                            <i class="fas fa-sync-alt me-2"></i>Làm mới
                        </button>
                        <button type="button" class="btn btn-primary" onclick="bulkActions()">
                            <i class="fas fa-tasks me-2"></i>Thao tác hàng loạt
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stats-card bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['total']) ?></h3>
                                    <p class="mb-0">Tổng bài đăng</p>
                                </div>
                                <i class="fas fa-home card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stats-card bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['pending']) ?></h3>
                                    <p class="mb-0">Chờ duyệt</p>
                                </div>
                                <i class="fas fa-clock card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stats-card bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['approved']) ?></h3>
                                    <p class="mb-0">Đã duyệt</p>
                                </div>
                                <i class="fas fa-check-circle card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stats-card bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['rejected']) ?></h3>
                                    <p class="mb-0">Từ chối</p>
                                </div>
                                <i class="fas fa-times-circle card-icon"></i>
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
                            <select class="form-select" name="status">
                                <option value="">🔍 Tất cả trạng thái</option>
                                <option value="<?= POST_STATUS_PENDING ?>" <?= $status == POST_STATUS_PENDING ? 'selected' : '' ?>>
                                    ⏳ Chờ duyệt
                                </option>
                                <option value="<?= POST_STATUS_APPROVED ?>" <?= $status == POST_STATUS_APPROVED ? 'selected' : '' ?>>
                                    ✅ Đã duyệt
                                </option>
                                <option value="<?= POST_STATUS_REJECTED ?>" <?= $status == POST_STATUS_REJECTED ? 'selected' : '' ?>>
                                    ❌ Từ chối
                                </option>
                                <option value="<?= POST_STATUS_HIDDEN ?>" <?= $status == POST_STATUS_HIDDEN ? 'selected' : '' ?>>
                                    👁️ Ẩn
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-search me-1"></i>
                                Tìm kiếm
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text"
                                       class="form-control"
                                       name="search"
                                       value="<?= e($search) ?>"
                                       placeholder="Tìm theo tiêu đề, người đăng, địa chỉ...">
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-search me-2"></i>Tìm kiếm
                            </button>
                            <a href="/admin/posts" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Posts List -->
            <div class="card posts-list-card admin-header-mobile admin-table-mobile">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Danh sách bài đăng
                        </h5>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-primary fs-6">
                                <?= $total ?> bài đăng
                            </span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="selectAll()">
                                    <i class="fas fa-check-square me-1"></i>Chọn tất cả
                                </button>
                                <button type="button" class="btn btn-outline-warning" onclick="bulkApprove()">
                                    <i class="fas fa-check me-1"></i>Duyệt
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="bulkReject()">
                                    <i class="fas fa-times me-1"></i>Từ chối
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($posts)): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-inbox fa-4x text-muted opacity-50"></i>
                            </div>
                            <h4 class="text-muted mb-3">Không có bài đăng nào</h4>
                            <p class="text-muted mb-4">Thử thay đổi bộ lọc để xem kết quả khác</p>
                            <a href="/admin/posts" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-2"></i>
                                Xem tất cả bài đăng
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($posts as $postItem): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="card post-card h-100 border-0 shadow-sm">
                                        <div class="card-body p-4">
                                            <div class="d-flex mb-3">
                                                <div class="post-thumbnail me-3">
                                                    <?= generateImageHtml(
                                                        $postItem['AnhDaiDien'],
                                                        e($postItem['TieuDe']),
                                                        'post-image'
                                                    ) ?>
                                                </div>

                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input post-checkbox"
                                                                   type="checkbox"
                                                                   value="<?= $postItem['ID'] ?>"
                                                                   id="post_<?= $postItem['ID'] ?>">
                                                        </div>

                                                        <?php
                                                        $statusClass = '';
                                                        $statusText = '';
                                                        $statusIcon = '';
                                                        switch ($postItem['TrangThai']) {
                                                            case POST_STATUS_PENDING:
                                                                $statusClass = 'bg-warning';
                                                                $statusText = 'Chờ duyệt';
                                                                $statusIcon = 'fas fa-clock';
                                                                break;
                                                            case POST_STATUS_APPROVED:
                                                                $statusClass = 'bg-success';
                                                                $statusText = 'Đã duyệt';
                                                                $statusIcon = 'fas fa-check';
                                                                break;
                                                            case POST_STATUS_REJECTED:
                                                                $statusClass = 'bg-danger';
                                                                $statusText = 'Từ chối';
                                                                $statusIcon = 'fas fa-times';
                                                                break;
                                                            case POST_STATUS_HIDDEN:
                                                                $statusClass = 'bg-secondary';
                                                                $statusText = 'Ẩn';
                                                                $statusIcon = 'fas fa-eye-slash';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?= $statusClass ?> fs-6">
                                                            <i class="<?= $statusIcon ?> me-1"></i>
                                                            <?= $statusText ?>
                                                        </span>
                                                    </div>

                                                    <h6 class="card-title mb-2">
                                                        <a href="/post/<?= $postItem['ID'] ?>"
                                                           class="text-decoration-none text-dark"
                                                           target="_blank"
                                                           title="<?= e($postItem['TieuDe']) ?>">
                                                            <?= e(truncateText($postItem['TieuDe'], 45)) ?>
                                                        </a>
                                                    </h6>

                                                    <p class="card-text text-muted mb-3" style="font-size: 0.9rem;">
                                                        <?php
                                                        // Generate excerpt from NoiDung since MoTa was removed
                                                        $excerpt = '';
                                                        if (!empty($postItem['NoiDung'])) {
                                                            // Use MarkdownHelper to create clean excerpt
                                                            $excerpt = \Tro365\Helpers\MarkdownHelper::createExcerpt($postItem['NoiDung'], 100);
                                                        }
                                                        echo e($excerpt);
                                                        ?>
                                                    </p>

                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-money-bill-wave text-success me-2"></i>
                                                                <span class="fw-bold text-success">
                                                                    <?= formatCurrency($postItem['Gia']) ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-eye text-info me-2"></i>
                                                                <span class="text-muted">
                                                                    <?= number_format($postItem['LuotXem']) ?> lượt xem
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-user text-primary me-2"></i>
                                                            <span class="text-muted">
                                                                <strong><?= e($postItem['NguoiDang']) ?></strong>
                                                            </span>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?= timeAgo($postItem['NgayTao']) ?>
                                                        </small>
                                                    </div>

                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($postItem['TrangThai'] == POST_STATUS_PENDING): ?>
                                                                <button class="btn btn-success"
                                                                        onclick="performAction('approve', <?= $postItem['ID'] ?>)"
                                                                        title="Duyệt bài đăng"
                                                                        data-bs-toggle="tooltip">
                                                                    <i class="fas fa-check me-1"></i>Duyệt
                                                                </button>
                                                                <button class="btn btn-danger"
                                                                        onclick="performAction('reject', <?= $postItem['ID'] ?>)"
                                                                        title="Từ chối bài đăng"
                                                                        data-bs-toggle="tooltip">
                                                                    <i class="fas fa-times me-1"></i>Từ chối
                                                                </button>
                                                            <?php elseif ($postItem['TrangThai'] == POST_STATUS_REJECTED): ?>
                                                                <button class="btn btn-success"
                                                                        onclick="performAction('approve', <?= $postItem['ID'] ?>)"
                                                                        title="Duyệt bài đăng"
                                                                        data-bs-toggle="tooltip">
                                                                    <i class="fas fa-check me-1"></i>Duyệt lại
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="dropdown posts-action-dropdown">
                                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                                    type="button"
                                                                    data-bs-toggle="dropdown"
                                                                    aria-expanded="false">
                                                                <i class="fas fa-cog me-1"></i>Thao tác
                                                            </button>
                                                            <ul class="dropdown-menu posts-actions-menu">
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                       href="/post/<?= $postItem['ID'] ?>"
                                                                       target="_blank">
                                                                        <i class="fas fa-external-link-alt me-2"></i>Xem chi tiết
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <button class="dropdown-item"
                                                                            onclick="editPost(<?= $postItem['ID'] ?>)">
                                                                        <i class="fas fa-edit me-2"></i>Chỉnh sửa
                                                                    </button>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <?php if ($postItem['TrangThai'] == POST_STATUS_APPROVED): ?>
                                                                    <li>
                                                                        <button class="dropdown-item text-warning"
                                                                                onclick="togglePostVisibility(<?= $postItem['ID'] ?>, '<?= e($postItem['TieuDe']) ?>', 'hide')">
                                                                            <i class="fas fa-eye-slash me-2"></i>Ẩn bài đăng
                                                                        </button>
                                                                    </li>
                                                                <?php elseif ($postItem['TrangThai'] == POST_STATUS_HIDDEN): ?>
                                                                    <li>
                                                                        <button class="dropdown-item text-success"
                                                                                onclick="togglePostVisibility(<?= $postItem['ID'] ?>, '<?= e($postItem['TieuDe']) ?>', 'show')">
                                                                            <i class="fas fa-eye me-2"></i>Hiện bài đăng
                                                                        </button>
                                                                    </li>
                                                                <?php endif; ?>
                                                                <li>
                                                                    <button class="dropdown-item text-danger"
                                                                            onclick="confirmDelete(<?= $postItem['ID'] ?>, '<?= e($postItem['TieuDe']) ?>')">
                                                                        <i class="fas fa-trash me-2"></i>Xóa bài đăng
                                                                    </button>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Phân trang">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                            <i class="fas fa-chevron-left"></i> Trước
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                            Sau <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Form -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="post_id" id="actionPostId">
</form>

<?php include __DIR__ . '/../../../includes/layouts/admin/footer.php'; ?>

<style>
/* ===== ADMIN POSTS DROPDOWN FIXES ===== */

/* Force all containers to allow overflow for dropdowns */
.posts-list-card,
.posts-list-card .card-body,
.posts-list-card .row,
.posts-list-card [class^="col-"],
.post-card {
    overflow: visible !important;
    position: relative;
}

/* High z-index for dropdown container */
.posts-action-dropdown {
    position: relative;
    z-index: 9999;
}

/* Critical z-index for dropdown menu */
.posts-actions-menu {
    z-index: 10000 !important;
    position: absolute !important;
}

/* Ensure dropdown menu is properly positioned and visible */
.posts-list-card .dropdown-menu {
    z-index: 10000 !important;
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
}

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
.posts-list-card .dropdown-item {
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

.posts-list-card .dropdown-item i {
    width: 1.1rem;
    text-align: center;
    color: #667085;
}

.posts-list-card .dropdown-item:hover {
    background: linear-gradient(135deg, rgba(102,126,234,.12), rgba(118,75,162,.12));
    transform: translateX(4px);
    color: #1f2937;
}

.posts-list-card .dropdown-item:hover i {
    color: #4f46e5;
}

.posts-list-card .dropdown-item.text-danger {
    color: #dc3545;
}

.posts-list-card .dropdown-item.text-danger:hover {
    background: rgba(220,53,69,0.12);
    color: #b02a37;
}

.posts-list-card .dropdown-item.text-warning {
    color: #f59e0b;
}

.posts-list-card .dropdown-item.text-warning:hover {
    background: rgba(245,158,11,0.12);
    color: #b45309;
}

.posts-list-card .dropdown-item.text-success {
    color: #198754;
}

.posts-list-card .dropdown-item.text-success:hover {
    background: rgba(25,135,84,0.12);
    color: #146c43;
}

.posts-list-card .dropdown-divider {
    margin: 0.35rem 0.25rem;
    border-color: rgba(0,0,0,0.08);
}

/* Post card hover state management */
.post-card {
    transition: all 0.3s ease;
    border-radius: 15px;
    position: relative;
    z-index: 1;
}

.post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    z-index: 2;
}

.post-card.dropdown-open {
    z-index: 9998 !important;
}

/* Post images */
.post-image {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 10px;
}

.post-image-placeholder {
    width: 80px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.post-thumbnail {
    position: relative;
}

.post-thumbnail::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 10px;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.post-card:hover .post-thumbnail::after {
    opacity: 1;
}

/* Responsive dropdown fixes */
@media (max-width: 768px) {
    .posts-actions-menu {
        right: 0 !important;
        left: auto !important;
        min-width: 200px;
    }
    
    .posts-list-card .dropdown-menu {
        min-width: 200px;
        max-width: calc(100vw - 2rem);
    }
    
    .posts-list-card .dropdown-item {
        padding: 0.5rem 0.7rem;
        font-size: 0.875rem;
    }

    .post-image, .post-image-placeholder {
        width: 60px;
        height: 45px;
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* Toggle visibility button styling */
.dropdown-item:hover {
    transition: all 0.3s ease;
}

.dropdown-item.text-warning:hover {
    background-color: #fff3cd;
    color: #856404 !important;
}

.dropdown-item.text-success:hover {
    background-color: #d1e7dd;
    color: #0f5132 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Enhanced dropdown management for posts
    const postDropdownToggles = document.querySelectorAll('.posts-action-dropdown .dropdown-toggle');
    
    postDropdownToggles.forEach(toggle => {
        const dropdown = toggle.closest('.posts-action-dropdown');
        const postCard = toggle.closest('.post-card');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');

        // Listen for dropdown show event
        dropdown.addEventListener('show.bs.dropdown', function(e) {
            console.log('Posts dropdown opening');
            
            // Add class to post card to prevent hover conflicts
            postCard.classList.add('dropdown-open');
            
            // Close any other open dropdowns
            const otherOpenCards = document.querySelectorAll('.post-card.dropdown-open');
            otherOpenCards.forEach(card => {
                if (card !== postCard) {
                    const otherDropdown = card.querySelector('.posts-action-dropdown');
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
            
            // Ensure dropdown menu is properly positioned
            setTimeout(() => {
                if (dropdownMenu) {
                    dropdownMenu.style.zIndex = '10000';
                    dropdownMenu.style.position = 'absolute';
                    
                    // Check if dropdown would go off-screen and adjust
                    const rect = dropdownMenu.getBoundingClientRect();
                    const viewportWidth = window.innerWidth;
                    
                    if (rect.right > viewportWidth) {
                        dropdownMenu.classList.add('dropdown-menu-end');
                    }
                }
            }, 10);
        });

        // Listen for dropdown shown event
        dropdown.addEventListener('shown.bs.dropdown', function() {
            console.log('Posts dropdown fully opened');
            // Force z-index after Bootstrap positioning
            if (dropdownMenu) {
                dropdownMenu.style.zIndex = '10000';
            }
        });

        // Listen for dropdown hide event
        dropdown.addEventListener('hide.bs.dropdown', function() {
            console.log('Posts dropdown closing');
            // Remove class to restore normal hover effects
            postCard.classList.remove('dropdown-open');
        });
        
        // Handle click outside to close
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
                if (bsDropdown && dropdown.classList.contains('show')) {
                    bsDropdown.hide();
                }
            }
        });
    });
    
    // Prevent dropdown from closing when clicking inside dropdown menu
    document.querySelectorAll('.posts-actions-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            // Allow buttons to work normally
            if (e.target.tagName === 'BUTTON') {
                return true;
            }
            
            // For non-button elements, stop propagation to prevent dropdown close
            if (e.target.tagName !== 'A') {
                e.stopPropagation();
            }
        });
    });
});

function performAction(action, postId) {
    let confirmMessage = '';
    let icon = '';

    switch (action) {
        case 'approve':
            confirmMessage = '✅ Bạn có chắc chắn muốn duyệt bài đăng này?';
            icon = '✅';
            break;
        case 'reject':
            confirmMessage = '❌ Bạn có chắc chắn muốn từ chối bài đăng này?';
            icon = '❌';
            break;
        case 'hide':
            confirmMessage = '👁️ Bạn có chắc chắn muốn ẩn bài đăng này?';
            icon = '👁️';
            break;
        default:
            confirmMessage = '⚠️ Bạn có chắc chắn muốn thực hiện hành động này?';
            icon = '⚠️';
    }

    if (confirm(confirmMessage)) {
        // Show loading state
        const buttons = document.querySelectorAll(`[onclick*="${postId}"]`);
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        });

        document.getElementById('actionType').value = action;
        document.getElementById('actionPostId').value = postId;
        document.getElementById('actionForm').submit();
    }
}

function confirmDelete(postId, title) {
    if (confirm(`🗑️ Bạn có chắc chắn muốn xóa bài đăng "${title}"?\n\n⚠️ Hành động này không thể hoàn tác.`)) {
        performAction('delete', postId);
    }
}

function editPost(postId) {
    // Redirect to edit page
    window.open(`/seller/posts/edit/${postId}`, '_blank');
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.post-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });

    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.post-checkbox:checked');
    const bulkButtons = document.querySelectorAll('[onclick*="bulk"]');

    bulkButtons.forEach(btn => {
        btn.disabled = checkedBoxes.length === 0;
    });
}

function bulkApprove() {
    const checkedBoxes = document.querySelectorAll('.post-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Vui lòng chọn ít nhất một bài đăng');
        return;
    }

    if (confirm(`✅ Duyệt ${checkedBoxes.length} bài đăng đã chọn?`)) {
        // Implementation for bulk approve
        alert('Chức năng duyệt hàng loạt sẽ được phát triển trong phiên bản tiếp theo.');
    }
}

function bulkReject() {
    const checkedBoxes = document.querySelectorAll('.post-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Vui lòng chọn ít nhất một bài đăng');
        return;
    }

    if (confirm(`❌ Từ chối ${checkedBoxes.length} bài đăng đã chọn?`)) {
        // Implementation for bulk reject
        alert('Chức năng từ chối hàng loạt sẽ được phát triển trong phiên bản tiếp theo.');
    }
}

function refreshPosts() {
    const btn = document.querySelector('[onclick="refreshPosts()"]');
    if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i>Đang làm mới...'; btn.disabled = true; }
    const params = new URLSearchParams(window.location.search);
    fetch(`/admin/posts?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(res => res.text())
      .then(html => {
        const temp = document.createElement('div'); temp.innerHTML = html;
        const newCard = temp.querySelector('.posts-list-card');
        const oldCard = document.querySelector('.posts-list-card');
        if (newCard && oldCard) {
          oldCard.replaceWith(newCard);
          showToast('Đã làm mới danh sách bài đăng', 'info');
        } else {
          window.location.reload();
        }
      })
      .catch(() => window.location.reload())
      .finally(() => { if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Làm mới'; btn.disabled = false; } });
}

function exportPosts() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Danh sách bài đăng - Trọ 365\n";
    csvContent += "Thời gian xuất: " + new Date().toLocaleString('vi-VN') + "\n\n";

    csvContent += "ID,Tiêu đề,Người đăng,Giá,Lượt xem,Trạng thái,Ngày tạo\n";

    // Get visible posts data
    const postCards = document.querySelectorAll('.post-card');
    postCards.forEach(card => {
        // Extract data from card (simplified)
        const title = card.querySelector('.card-title a')?.textContent.trim() || '';
        const author = card.querySelector('.text-muted strong')?.textContent.trim() || '';
        const price = card.querySelector('.text-success')?.textContent.trim() || '';
        const views = card.querySelector('.text-muted')?.textContent.match(/\d+/)?.[0] || '0';
        const status = card.querySelector('.badge')?.textContent.trim() || '';

        csvContent += `"","${title}","${author}","${price}","${views}","${status}",""\n`;
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "bai-dang-" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function bulkActions() {
    alert('Chức năng thao tác hàng loạt sẽ được phát triển trong phiên bản tiếp theo.');
}

// Toggle post visibility (hide/show)
function togglePostVisibility(postId, postTitle, action) {
    const actionText = action === 'hide' ? 'ẨN' : 'HIỆN';
    const description = action === 'hide'
        ? 'Bài đăng sẽ không hiển thị cho người dùng.'
        : 'Bài đăng sẽ hiển thị trở lại cho người dùng.';

    if (confirm(`Bạn có chắc muốn ${actionText} bài đăng "${postTitle}"?\n\n${description}`)) {
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="toggle_visibility">
            <input type="hidden" name="post_id" value="${postId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Add event listeners for checkboxes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('post-checkbox')) {
        updateBulkActions();
    }
});
</script>

</body>
</html>
