<?php
/**
 * Seller Posts Management
 * Tro365 - Website thuê trọ
 */

// Load autoloader and configuration
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/functions/helpers.php';
require_once __DIR__ . '/../../../includes/functions/auth.php';
require_once __DIR__ . '/../../../includes/functions/validation.php';

use Tro365\Core\Auth;
use Tro365\Models\Post;

$auth = new Auth();
$post = new Post();

// Require seller role
$auth->requireSeller();

$currentUser = $auth->getCurrentUser();

// Get filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = getPostsPerPage();

// Build filters
$filters = [
    'user_id' => $currentUser['ID']
];

if (!empty($status)) {
    $filters['status'] = $status;
}

if (!empty($search)) {
    $filters['search'] = $search;
}

// Get posts
$posts = $post->getAll($page, $limit, $filters);
$total = $post->count($filters);
$totalPages = ceil($total / $limit);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }
        
        $action = $_POST['action'] ?? '';
        $postId = (int)($_POST['post_id'] ?? 0);
        
        // Verify ownership
        if (!$post->canEdit($postId, $currentUser['ID'], $currentUser['VaiTroID'])) {
            throw new Exception('Bạn không có quyền thực hiện hành động này');
        }
        
        switch ($action) {
            case 'delete':
                $post->delete($postId);
                setFlashMessage(MSG_SUCCESS, 'Xóa bài đăng thành công');
                break;
                
            case 'hide':
                $post->update($postId, ['TrangThai' => POST_STATUS_HIDDEN]);
                setFlashMessage(MSG_SUCCESS, 'Ẩn bài đăng thành công');
                break;
                
            case 'resubmit':
                $post->update($postId, ['TrangThai' => POST_STATUS_PENDING]);
                setFlashMessage(MSG_SUCCESS, 'Gửi lại bài đăng để duyệt thành công');
                break;
        }
        
        redirect('/seller/posts');
        
    } catch (Exception $e) {
        setFlashMessage(MSG_ERROR, $e->getMessage());
    }
}

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bài đăng - <?= getWebsiteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/client/main.css" rel="stylesheet">
    <link href="/assets/css/seller/seller-main.css" rel="stylesheet">
    <link href="/assets/css/seller/seller-posts.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../../includes/layouts/client/header.php'; ?>

    <div class="seller-posts-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="/seller">Dashboard</a></li>
                <li class="breadcrumb-item active">Quản lý bài đăng</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="seller-posts-header seller-fade-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="seller-posts-title">
                        <i class="fas fa-list-alt"></i>
                        Quản lý bài đăng
                    </h2>
                    <p class="seller-posts-subtitle">
                        Quản lý và theo dõi tất cả bài đăng của bạn
                    </p>
                </div>
                <a href="/seller/posts/create" class="seller-action-card" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 1.5rem; text-decoration: none; border-radius: 12px; background: var(--seller-primary); color: white; font-weight: 500;">
                    <i class="fas fa-plus"></i>
                    Tạo bài đăng mới
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === MSG_SUCCESS ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="seller-posts-filters seller-slide-up">
            <form method="GET">
                <div class="seller-filter-row">
                    <div class="seller-filter-group">
                        <label class="seller-filter-label">Tìm kiếm</label>
                        <input type="text"
                               class="seller-filter-input"
                               name="search"
                               value="<?= e($search) ?>"
                               placeholder="Tìm theo tiêu đề, mô tả...">
                    </div>
                    <div class="seller-filter-group">
                        <label class="seller-filter-label">Trạng thái</label>
                        <select class="seller-filter-select" name="status">
                            <option value="">Tất cả</option>
                            <option value="<?= POST_STATUS_PENDING ?>" <?= $status == POST_STATUS_PENDING ? 'selected' : '' ?>>
                                Chờ duyệt
                            </option>
                            <option value="<?= POST_STATUS_APPROVED ?>" <?= $status == POST_STATUS_APPROVED ? 'selected' : '' ?>>
                                Đã duyệt
                            </option>
                            <option value="<?= POST_STATUS_REJECTED ?>" <?= $status == POST_STATUS_REJECTED ? 'selected' : '' ?>>
                                Từ chối
                            </option>
                            <option value="<?= POST_STATUS_HIDDEN ?>" <?= $status == POST_STATUS_HIDDEN ? 'selected' : '' ?>>
                                Ẩn
                            </option>
                        </select>
                    </div>
                    <div class="seller-filter-group">
                        <button type="submit" class="seller-action-card" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; text-decoration: none; border: none; border-radius: 8px; background: var(--seller-primary); color: white; font-weight: 500; cursor: pointer;">
                            <i class="fas fa-search"></i> Lọc
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Posts List -->
        <?php if (empty($posts)): ?>
            <div class="seller-posts-table-container">
                <div class="seller-posts-empty">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="empty-title">Chưa có bài đăng nào</div>
                    <p class="empty-description">Hãy tạo bài đăng đầu tiên để bắt đầu kinh doanh trên Trọ 365</p>
                    <a href="/seller/posts/create" class="empty-action">
                        <i class="fas fa-plus"></i>
                        Tạo bài đăng mới
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="seller-posts-table-container seller-slide-up">
                <table class="seller-posts-table">
                    <thead>
                        <tr>
                            <th>Bài đăng</th>
                            <th>Giá</th>
                            <th>Trạng thái</th>
                            <th>Lượt xem</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $postItem): ?>
                            <tr>
                                <td>
                                    <a href="/post/<?= $postItem['ID'] ?>" class="seller-post-title" target="_blank">
                                        <?= e(truncateText($postItem['TieuDe'], 60)) ?>
                                    </a>
                                    <div class="seller-post-meta">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= e($postItem['DiaChi'] ?? 'Chưa cập nhật') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="seller-post-price">
                                        <?= formatCurrency($postItem['Gia']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($postItem['TrangThai']) {
                                        case POST_STATUS_PENDING:
                                            $statusClass = 'pending';
                                            $statusText = 'Chờ duyệt';
                                            break;
                                        case POST_STATUS_APPROVED:
                                            $statusClass = 'approved';
                                            $statusText = 'Đã duyệt';
                                            break;
                                        case POST_STATUS_REJECTED:
                                            $statusClass = 'rejected';
                                            $statusText = 'Từ chối';
                                            break;
                                        case POST_STATUS_HIDDEN:
                                            $statusClass = 'hidden';
                                            $statusText = 'Ẩn';
                                            break;
                                    }
                                    ?>
                                    <span class="seller-post-status <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="seller-post-meta">
                                        <i class="fas fa-eye"></i>
                                        <?= number_format($postItem['LuotXem']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="seller-post-meta">
                                        <i class="fas fa-calendar"></i>
                                        <?= timeAgo($postItem['NgayTao']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="seller-post-actions">
                                        <a href="/post/<?= $postItem['ID'] ?>"
                                           class="seller-action-btn view"
                                           title="Xem bài đăng"
                                           target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/seller/posts/edit/<?= $postItem['ID'] ?>"
                                           class="seller-action-btn edit"
                                           title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="seller-action-btn delete"
                                                title="Xóa bài đăng"
                                                onclick="confirmDelete(<?= $postItem['ID'] ?>, '<?= e($postItem['TieuDe']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="seller-pagination">
                    <?php if ($page > 1): ?>
                        <a class="seller-pagination-btn" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                            <i class="fas fa-chevron-left"></i> Trước
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a class="seller-pagination-btn <?= $i == $page ? 'active' : '' ?>"
                           href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a class="seller-pagination-btn" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                            Sau <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Summary -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h4 class="text-primary"><?= number_format($total) ?></h4>
                                <small class="text-muted">Tổng bài đăng</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-warning">
                                    <?= number_format($post->count(['user_id' => $currentUser['ID'], 'status' => POST_STATUS_PENDING])) ?>
                                </h4>
                                <small class="text-muted">Chờ duyệt</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-success">
                                    <?= number_format($post->count(['user_id' => $currentUser['ID'], 'status' => POST_STATUS_APPROVED])) ?>
                                </h4>
                                <small class="text-muted">Đã duyệt</small>
                            </div>
                            <div class="col-md-3">
                                <h4 class="text-danger">
                                    <?= number_format($post->count(['user_id' => $currentUser['ID'], 'status' => POST_STATUS_REJECTED])) ?>
                                </h4>
                                <small class="text-muted">Từ chối</small>
                            </div>
                        </div>
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

    <?php include __DIR__ . '/../../../includes/layouts/client/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function performAction(action, postId) {
            document.getElementById('actionType').value = action;
            document.getElementById('actionPostId').value = postId;
            document.getElementById('actionForm').submit();
        }
        
        function confirmDelete(postId, title) {
            if (confirm(`Bạn có chắc chắn muốn xóa bài đăng "${title}"?\n\nHành động này không thể hoàn tác.`)) {
                performAction('delete', postId);
            }
        }
    </script>
</body>
</html>
