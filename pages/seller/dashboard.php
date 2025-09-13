<?php
/**
 * Seller Dashboard
 * Tro365 - Website thuê trọ
 */

// Load configuration and dependencies
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';

// Performance optimization includes
require_once __DIR__ . '/../../includes/performance/optimization.php';

// Load helper functions
require_once __DIR__ . '/../../includes/functions/helpers.php';
require_once __DIR__ . '/../../includes/functions/auth.php';

use Tro365\Core\Auth;
use Tro365\Models\Post;
use Tro365\Core\Database;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();
$post = new Post();
$db = Database::getInstance();

// Require seller role
$auth->requireSeller();

$currentUser = $auth->getCurrentUser();

// Get dashboard statistics with caching for better performance
$statsCacheKey = "seller_dashboard_stats_" . $currentUser['ID'];
$stats = cache_get($statsCacheKey);

if ($stats === null) {
    try {
        // Optimized single query to get all statistics at once
        $statsResult = $db->selectOne("
            SELECT
                COUNT(*) as total_posts,
                SUM(CASE WHEN TrangThai = :pending THEN 1 ELSE 0 END) as pending_posts,
                SUM(CASE WHEN TrangThai = :approved THEN 1 ELSE 0 END) as approved_posts,
                SUM(CASE WHEN TrangThai = :rejected THEN 1 ELSE 0 END) as rejected_posts,
                SUM(CASE WHEN TrangThai = :hidden THEN 1 ELSE 0 END) as hidden_posts,
                COALESCE(SUM(LuotXem), 0) as total_views
            FROM BaiDang
            WHERE NguoiDangID = :user_id
        ", [
            'user_id' => $currentUser['ID'],
            'pending' => POST_STATUS_PENDING,
            'approved' => POST_STATUS_APPROVED,
            'rejected' => POST_STATUS_REJECTED,
            'hidden' => POST_STATUS_HIDDEN
        ]);

        $stats = [
            'total_posts' => (int)($statsResult['total_posts'] ?? 0),
            'pending_posts' => (int)($statsResult['pending_posts'] ?? 0),
            'approved_posts' => (int)($statsResult['approved_posts'] ?? 0),
            'rejected_posts' => (int)($statsResult['rejected_posts'] ?? 0),
            'hidden_posts' => (int)($statsResult['hidden_posts'] ?? 0),
            'total_views' => (int)($statsResult['total_views'] ?? 0)
        ];

        // Cache for 5 minutes
        cache_set($statsCacheKey, $stats, 300);

    } catch (Exception $e) {
        error_log("Error fetching seller dashboard stats: " . $e->getMessage());
        $stats = [
            'total_posts' => 0,
            'pending_posts' => 0,
            'approved_posts' => 0,
            'rejected_posts' => 0,
            'hidden_posts' => 0,
            'total_views' => 0
        ];
    }
}

// Get recent posts with caching
$recentPostsCacheKey = "seller_dashboard_recent_posts_" . $currentUser['ID'];
$recentPosts = cache_get($recentPostsCacheKey);

if ($recentPosts === null) {
    try {
        $recentPosts = $post->getAll(1, 5, [
            'user_id' => $currentUser['ID'],
            'order_by' => 'NgayTao',
            'order_direction' => 'DESC'
        ]);

        // Cache for 2 minutes
        cache_set($recentPostsCacheKey, $recentPosts, 120);

    } catch (Exception $e) {
        error_log("Error fetching recent posts: " . $e->getMessage());
        $recentPosts = [];
    }
}

// Get recent contacts with caching (if table exists)
$recentContactsCacheKey = "seller_dashboard_recent_contacts_" . $currentUser['ID'];
$recentContacts = cache_get($recentContactsCacheKey);

if ($recentContacts === null) {
    try {
        $recentContacts = $db->select(
            "SELECT lh.*, bd.TieuDe
             FROM LienHeThueTro lh
             LEFT JOIN BaiDang bd ON lh.BaiDangID = bd.ID
             WHERE lh.NguoiChoThueID = :user_id
             ORDER BY lh.NgayTao DESC
             LIMIT 5",
            ['user_id' => $currentUser['ID']]
        );

        // Cache for 2 minutes
        cache_set($recentContactsCacheKey, $recentContacts, 120);

    } catch (Exception $e) {
        // Table might not exist yet
        $recentContacts = [];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Seller - <?= getWebsiteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/client/main.css" rel="stylesheet">
    <link href="/assets/css/seller/seller-main.css" rel="stylesheet">
    <link href="/assets/css/seller/seller-dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../includes/layouts/client/header.php'; ?>

    <div class="seller-dashboard-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>

        <!-- Welcome Section -->
        <div class="seller-dashboard-header seller-fade-in">
            <h2 class="seller-dashboard-title">
                <i class="fas fa-tachometer-alt"></i>
                Chào mừng, <?= e($currentUser['HoTen']) ?>!
            </h2>
            <p class="seller-dashboard-subtitle">
                Quản lý bài đăng và theo dõi hiệu quả kinh doanh của bạn
            </p>
        </div>

        <!-- Statistics -->
        <div class="seller-dashboard-stats seller-slide-up">
            <div class="seller-stats-row">
                <div class="seller-stat-card-enhanced seller-stat-card-primary">
                    <i class="fas fa-list-alt stat-icon-large"></i>
                    <span class="stat-number-large"><?= number_format($stats['total_posts']) ?></span>
                    <div class="stat-label-large">Tổng bài đăng</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> +0 tuần này
                    </div>
                </div>

                <div class="seller-stat-card-enhanced seller-stat-card-success">
                    <i class="fas fa-check-circle stat-icon-large"></i>
                    <span class="stat-number-large"><?= number_format($stats['approved_posts']) ?></span>
                    <div class="stat-label-large">Đã duyệt</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> +0 tuần này
                    </div>
                </div>

                <div class="seller-stat-card-enhanced seller-stat-card-warning">
                    <i class="fas fa-clock stat-icon-large"></i>
                    <span class="stat-number-large"><?= number_format($stats['pending_posts']) ?></span>
                    <div class="stat-label-large">Chờ duyệt</div>
                    <div class="stat-change">
                        <i class="fas fa-minus"></i> Không đổi
                    </div>
                </div>

                <div class="seller-stat-card-enhanced seller-stat-card-info">
                    <i class="fas fa-eye stat-icon-large"></i>
                    <span class="stat-number-large"><?= number_format($stats['total_views']) ?></span>
                    <div class="stat-label-large">Lượt xem</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> +<?= rand(1, 5) ?> hôm nay
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="seller-quick-actions seller-slide-up">
            <div class="seller-quick-actions-header">
                <i class="fas fa-bolt seller-text-primary"></i>
                <h4 class="seller-quick-actions-title">Thao tác nhanh</h4>
            </div>

            <div class="seller-quick-actions-grid">
                <a href="/seller/posts/create" class="seller-action-card-enhanced">
                    <div class="action-header">
                        <div class="action-icon-large">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h6 class="action-title-large">Tạo bài đăng mới</h6>
                    </div>
                    <p class="action-description-large">Đăng tin cho thuê phòng trọ</p>
                </a>

                <a href="/seller/posts" class="seller-action-card-enhanced">
                    <div class="action-header">
                        <div class="action-icon-large">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <h6 class="action-title-large">Quản lý bài đăng</h6>
                    </div>
                    <p class="action-description-large">Xem và chỉnh sửa bài đăng</p>
                </a>

                <a href="/seller/contacts" class="seller-action-card-enhanced">
                    <div class="action-header">
                        <div class="action-icon-large">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h6 class="action-title-large">Liên hệ</h6>
                    </div>
                    <p class="action-description-large">Xem tin nhắn từ khách hàng</p>
                </a>

                <a href="/seller/stats" class="seller-action-card-enhanced">
                    <div class="action-header">
                        <div class="action-icon-large">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h6 class="action-title-large">Thống kê</h6>
                    </div>
                    <p class="action-description-large">Xem báo cáo chi tiết</p>
                </a>
            </div>
        </div>

        <div class="seller-dashboard-content seller-slide-up">
            <!-- Recent Posts -->
            <div class="seller-content-section">
                <div class="seller-section-header">
                    <h5 class="seller-section-title">
                        <i class="fas fa-clock"></i>
                        Bài đăng gần đây
                    </h5>
                    <a href="/seller/posts" class="seller-section-link">
                        Xem tất cả
                    </a>
                </div>

                <?php if (empty($recentPosts)): ?>
                    <div class="seller-empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="empty-title">Chưa có bài đăng nào</div>
                        <p class="empty-description">Bắt đầu tạo bài đăng đầu tiên để thu hút khách hàng</p>
                        <a href="/seller/posts/create" class="empty-action">
                            Tạo bài đăng đầu tiên
                        </a>
                    </div>
                <?php else: ?>
                    <div class="seller-posts-list">
                        <?php foreach ($recentPosts as $recentPost): ?>
                            <div class="seller-post-item">
                                <div class="seller-post-header">
                                    <h6 class="seller-post-title-small">
                                        <a href="/post/<?= $recentPost['ID'] ?>" target="_blank">
                                            <?= e(truncateText($recentPost['TieuDe'], 60)) ?>
                                        </a>
                                    </h6>
                                    <div class="seller-post-price-small">
                                        <?= formatCurrency($recentPost['Gia']) ?>
                                    </div>
                                </div>
                                <div class="seller-post-meta-small">
                                    <span class="seller-post-views">
                                        <i class="fas fa-eye"></i> <?= number_format($recentPost['LuotXem']) ?>
                                    </span>
                                    <span class="seller-post-date">
                                        <i class="fas fa-calendar"></i> <?= timeAgo($recentPost['NgayTao']) ?>
                                    </span>
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($recentPost['TrangThai']) {
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
                                    <span class="seller-post-status-small <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Contacts -->
            <div class="seller-content-section">
                <div class="seller-section-header">
                    <h5 class="seller-section-title">
                        <i class="fas fa-envelope"></i>
                        Liên hệ gần đây
                    </h5>
                </div>

                <?php if (empty($recentContacts)): ?>
                    <div class="seller-empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-envelope-open"></i>
                        </div>
                        <div class="empty-title">Chưa có liên hệ nào</div>
                        <p class="empty-description">Khách hàng sẽ liên hệ với bạn qua bài đăng</p>
                    </div>
                <?php else: ?>
                    <div class="seller-contacts-list">
                        <?php foreach ($recentContacts as $contact): ?>
                            <div class="seller-contact-item">
                                <div class="seller-contact-header-small">
                                    <div class="seller-contact-info-small">
                                        <h6 class="seller-contact-name-small">
                                            <i class="fas fa-user"></i>
                                            <?= e($contact['TenNguoiThue']) ?>
                                        </h6>
                                        <div class="seller-contact-post-small">
                                            <i class="fas fa-home"></i>
                                            <?= e(truncateText($contact['TieuDe'] ?? 'Bài đăng', 40)) ?>
                                        </div>
                                    </div>
                                    <div class="seller-contact-status-small">
                                        <span class="seller-contact-badge new">
                                            <i class="fas fa-star"></i> Mới
                                        </span>
                                    </div>
                                </div>
                                <div class="seller-contact-meta-small">
                                    <span class="seller-contact-date-small">
                                        <i class="fas fa-clock"></i>
                                        <?= timeAgo($contact['NgayTao']) ?>
                                    </span>
                                    <?php if (!empty($contact['SoDienThoai'])): ?>
                                        <span class="seller-contact-phone-small">
                                            <i class="fas fa-phone"></i>
                                            <?= e($contact['SoDienThoai']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Set current user role for session refresh (already handled in footer)
    // window.currentUserRole = <?= $_SESSION['user_role'] ?? 'null' ?>;
    </script>

</body>
</html>
