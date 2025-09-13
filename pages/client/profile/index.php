<?php
/**
 * User Profile Page - Glass Morphism Design
 * Tro365 - Website thuê trọ
 * Modern responsive profile with enhanced features
 */

// Load configuration and dependencies
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/constants.php';

// Performance optimization includes
require_once __DIR__ . '/../../../includes/performance/optimization.php';

// Load helper functions
require_once __DIR__ . '/../../../includes/functions/helpers.php';
require_once __DIR__ . '/../../../includes/functions/auth.php';

use Tro365\Core\Auth;
use Tro365\Models\Post;
use Tro365\Core\Database;
use Tro365\Models\Activity;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

// Helper function to get activity icons
function getActivityIcon($description) {
    $description = strtolower($description);

    if (strpos($description, 'đăng nhập') !== false) {
        return 'fas fa-sign-in-alt';
    } elseif (strpos($description, 'đăng xuất') !== false) {
        return 'fas fa-sign-out-alt';
    } elseif (strpos($description, 'đăng bài') !== false || strpos($description, 'tạo bài') !== false) {
        return 'fas fa-plus-circle';
    } elseif (strpos($description, 'chỉnh sửa') !== false || strpos($description, 'cập nhật') !== false) {
        return 'fas fa-edit';
    } elseif (strpos($description, 'xem') !== false || strpos($description, 'truy cập') !== false) {
        return 'fas fa-eye';
    } elseif (strpos($description, 'yêu thích') !== false || strpos($description, 'like') !== false) {
        return 'fas fa-heart';
    } elseif (strpos($description, 'bình luận') !== false || strpos($description, 'comment') !== false) {
        return 'fas fa-comment';
    } elseif (strpos($description, 'chia sẻ') !== false || strpos($description, 'share') !== false) {
        return 'fas fa-share';
    } elseif (strpos($description, 'tìm kiếm') !== false || strpos($description, 'search') !== false) {
        return 'fas fa-search';
    } elseif (strpos($description, 'thanh toán') !== false || strpos($description, 'payment') !== false) {
        return 'fas fa-credit-card';
    } else {
        return 'fas fa-clock';
    }
}

$auth = new Auth();
$post = new Post();
$db = Database::getInstance();
$activity = new Activity();

// Require login
if (!$auth->isLoggedIn()) {
    setFlashMessage(MSG_ERROR, 'Vui lòng đăng nhập để xem trang cá nhân');
    redirect('/login');
}

// Get current user with fresh data (auto-refreshes every 5 minutes)
$currentUser = $auth->getCurrentUser(true); // Force refresh to get latest email verification status



// Get enhanced user statistics
$stats = [
    'total_posts' => 0,
    'approved_posts' => 0,
    'total_views' => 0,
    'favorite_posts' => 0,
    'join_days' => 0,
    'profile_completion' => 0
];

if ($currentUser['VaiTroID'] >= ROLE_SELLER) {
    $cacheKeyTP = 'profile:total_posts:' . $currentUser['ID'];
    $cacheKeyAP = 'profile:approved_posts:' . $currentUser['ID'];
    $cacheKeyTV = 'profile:total_views:' . $currentUser['ID'];

    $stats['total_posts'] = cache_get($cacheKeyTP, null, 120);
    if ($stats['total_posts'] === null) {
        $stats['total_posts'] = $post->count(['user_id' => $currentUser['ID']]);
        cache_set($cacheKeyTP, $stats['total_posts']);
    }

    $stats['approved_posts'] = cache_get($cacheKeyAP, null, 120);
    if ($stats['approved_posts'] === null) {
        $stats['approved_posts'] = $post->count(['user_id' => $currentUser['ID'], 'status' => POST_STATUS_APPROVED]);
        cache_set($cacheKeyAP, $stats['approved_posts']);
    }

    $totalViews = cache_get($cacheKeyTV, null, 120);
    if ($totalViews === null) {
        $viewsResult = $db->selectOne(
            "SELECT SUM(LuotXem) as total_views FROM BaiDang WHERE NguoiDangID = :user_id",
            ['user_id' => $currentUser['ID']]
        );
        $totalViews = (int)($viewsResult['total_views'] ?? 0);
        cache_set($cacheKeyTV, $totalViews);
    }
    $stats['total_views'] = $totalViews;
}

// Calculate join days
$joinDate = new DateTime($currentUser['NgayTao'] ?? date('Y-m-d'));
$currentDate = new DateTime();
$stats['join_days'] = $joinDate->diff($currentDate)->days;

// Calculate profile completion percentage
$profileFields = ['HoTen', 'Email', 'SDT', 'NgaySinh', 'GioiTinh', 'AnhDaiDien'];
$completedFields = 0;
foreach ($profileFields as $field) {
    if (!empty($currentUser[$field])) {
        $completedFields++;
    }
}
$stats['profile_completion'] = round(($completedFields / count($profileFields)) * 100);

// Get favorite posts count and data
$favCountKey = 'profile:favorites_count:' . $currentUser['ID'];
$favListKey  = 'profile:favorites_list:' . $currentUser['ID'];

// TEMPORARY HARDCODE FOR TESTING
$favoriteCount = 2; // Hardcode to test display
$stats['favorite_posts'] = $favoriteCount;

// Get favorite posts for display (disable cache temporarily)
$favoritePosts = [];
if ($favoriteCount > 0) {
    // $favoritePosts = cache_get($favListKey, null, 120); // Disabled temporarily
    $favoritePosts = null; // Force fresh query
    if ($favoritePosts === null) {
        $favoritePosts = $db->select(
            "SELECT bd.*, yt.NgayTao as favorite_date
             FROM YeuThich yt
             JOIN BaiDang bd ON yt.BaiDangID = bd.ID
             WHERE yt.KhachHangID = :user_id
             AND bd.TrangThai = :status
             ORDER BY yt.NgayTao DESC
             LIMIT 10",
            [
                'user_id' => $currentUser['ID'],
                'status' => POST_STATUS_APPROVED
            ]
        );
        // cache_set($favListKey, $favoritePosts); // Disabled temporarily
    }
}

// Get recent activities
$recentActivities = cache_get('profile:recent_activities:' . $currentUser['ID'], null, 120);
if ($recentActivities === null) {
    $userActivities = $activity->getUserActivities($currentUser['ID'], 5);
    $recentActivities = [];
    foreach ($userActivities as $act) {
        $recentActivities[] = $activity->formatActivity($act);
    }
    cache_set('profile:recent_activities:' . $currentUser['ID'], $recentActivities);
}
// Set page variables for header
$pageTitle = 'Trang cá nhân - ' . e($currentUser['HoTen']);
$pageDescription = 'Thông tin cá nhân và quản lý tài khoản với giao diện Glass Morphism hiện đại';

// Additional CSS for profile page
$additionalCSS = [
    asset_url('css/client/glass-morphism.css'),
    asset_url('css/client/profile.css')
];

// Additional JS for profile page
$additionalJS = [
    asset_url('js/client/profile.js')
];

// Include header
include __DIR__ . '/../../../includes/layouts/client/header.php';
?>

<!-- Profile Container -->
<div class="profile-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="my-3">
        <ol class="breadcrumb glass-container" style="padding: 1rem; margin: 0;">
            <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item active">Thông tin cá nhân</li>
        </ol>
    </nav>

    <!-- Profile Hero Section -->
    <section class="profile-hero">
        <div class="profile-hero-content">
            <div class="profile-avatar-container">
                <?= getUserAvatarHtml($currentUser['AnhDaiDien'], 'profile-avatar', 'Avatar') ?>
                <div class="profile-avatar-badge">
                    <?php if (!empty($currentUser['email_verified_at'])): ?>
                        <i class="fas fa-check-circle text-success"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-info">
                <h2><?= e($currentUser['HoTen']) ?></h2>

                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= e($currentUser['Email']) ?></span>
                        <?php if (!empty($currentUser['email_verified_at'])): ?>
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check-circle me-1"></i>
                                Đã xác thực
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Chưa xác thực
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($currentUser['SDT'])): ?>
                        <div class="profile-info-item">
                            <i class="fas fa-phone"></i>
                            <span><?= e($currentUser['SDT']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($currentUser['NgaySinh'])): ?>
                        <div class="profile-info-item">
                            <i class="fas fa-birthday-cake"></i>
                            <span>Sinh ngày <?= formatDate($currentUser['NgaySinh']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($currentUser['GioiTinh'])): ?>
                        <div class="profile-info-item">
                            <i class="fas fa-venus-mars"></i>
                            <span><?= e($currentUser['GioiTinh']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="profile-info-item">
                        <i class="fas fa-calendar"></i>
                        <span>Tham gia từ <?= formatDate($currentUser['NgayTao'] ?? date('Y-m-d')) ?></span>
                    </div>
                </div>

                <?php
                $roleClass = '';
                $roleText = '';
                $roleIcon = '';
                switch ($currentUser['VaiTroID']) {
                    case ROLE_ADMIN:
                        $roleClass = 'bg-danger';
                        $roleText = 'Quản trị viên';
                        $roleIcon = 'fas fa-crown';
                        break;
                    case ROLE_MODERATOR:
                        $roleClass = 'bg-warning';
                        $roleText = 'Điều hành viên';
                        $roleIcon = 'fas fa-shield-alt';
                        break;
                    case ROLE_SELLER:
                        $roleClass = 'bg-success';
                        $roleText = 'Seller';
                        $roleIcon = 'fas fa-store';
                        break;
                    default:
                        $roleClass = 'bg-primary';
                        $roleText = 'Thành viên';
                        $roleIcon = 'fas fa-user';
                }
                ?>
                <div class="profile-role-badge">
                    <i class="<?= $roleIcon ?>"></i>
                    <span><?= $roleText ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Navigation -->
    <nav class="profile-nav">
        <ul class="nav nav-pills" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active"
                        id="overview-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#overview"
                        type="button"
                        role="tab">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    <span>Tổng quan</span>
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link"
                        id="favorites-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#favorites"
                        type="button"
                        role="tab">
                    <i class="fas fa-heart me-2"></i>
                    <span>Yêu thích</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link"
                        id="achievements-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#achievements"
                        type="button"
                        role="tab">
                    <i class="fas fa-trophy me-2"></i>
                    <span>Thành tích</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link"
                        id="settings-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#settings"
                        type="button"
                        role="tab">
                    <i class="fas fa-cog me-2"></i>
                    <span>Cài đặt</span>
                </button>
            </li>
        </ul>
    </nav>

    <!-- Tab Content -->
    <div class="tab-content" id="profileTabsContent">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <!-- Profile Statistics -->
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="fas fa-chart-bar"></i>
                    Thống kê tổng quan
                </h3>

                <div class="profile-stats-grid">
                    <div class="profile-stat-card" data-stat="total_posts">
                        <div class="profile-stat-content">
                            <div class="profile-stat-info">
                                <h3><?= number_format($stats['total_posts']) ?></h3>
                                <p>Tổng bài đăng</p>
                            </div>
                            <div class="profile-stat-icon">
                                <i class="fas fa-list-alt"></i>
                            </div>
                        </div>
                    </div>

                    <?php if ($currentUser['VaiTroID'] >= ROLE_SELLER): ?>
                        <div class="profile-stat-card" data-stat="approved_posts">
                            <div class="profile-stat-content">
                                <div class="profile-stat-info">
                                    <h3><?= number_format($stats['approved_posts']) ?></h3>
                                    <p>Bài đã duyệt</p>
                                </div>
                                <div class="profile-stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>

                        <div class="profile-stat-card" data-stat="total_views">
                            <div class="profile-stat-content">
                                <div class="profile-stat-info">
                                    <h3><?= number_format($stats['total_views']) ?></h3>
                                    <p>Lượt xem</p>
                                </div>
                                <div class="profile-stat-icon">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="profile-stat-card" data-stat="favorite_posts">
                        <div class="profile-stat-content">
                            <div class="profile-stat-info">
                                <h3><?= number_format($stats['favorite_posts']) ?></h3>
                                <p>Yêu thích</p>
                            </div>
                            <div class="profile-stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                        </div>
                    </div>

                    <div class="profile-stat-card" data-stat="join_days">
                        <div class="profile-stat-content">
                            <div class="profile-stat-info">
                                <h3><?= number_format($stats['join_days']) ?></h3>
                                <p>Ngày tham gia</p>
                            </div>
                            <div class="profile-stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>

                    <div class="profile-stat-card">
                        <div class="profile-stat-content">
                            <div class="profile-stat-info">
                                <h3><?= $stats['profile_completion'] ?>%</h3>
                                <p>Hồ sơ hoàn thiện</p>
                            </div>
                            <div class="profile-stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities - Glass Morphism Design -->
            <div class="profile-section">
                <div class="activity-header">
                    <h3 class="profile-section-title">
                        <i class="fas fa-history activity-icon"></i>
                        Hoạt động gần đây
                    </h3>
                    <button class="activity-view-all" type="button">
                        <span>Xem tất cả</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>

                <div class="activity-container">
                    <?php if (empty($recentActivities)): ?>
                        <div class="activity-empty-state">
                            <div class="empty-animation">
                                <div class="empty-circle">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="empty-waves">
                                    <div class="wave wave-1"></div>
                                    <div class="wave wave-2"></div>
                                    <div class="wave wave-3"></div>
                                </div>
                            </div>
                            <h5 class="empty-title">Chưa có hoạt động nào</h5>
                            <p class="empty-description">Các hoạt động của bạn sẽ xuất hiện tại đây</p>
                        </div>
                    <?php else: ?>
                        <div class="activity-grid">
                            <?php foreach ($recentActivities as $index => $activityItem): ?>
                                <div class="activity-card" data-activity-type="<?= $activityItem['type'] ?? 'default' ?>" style="animation-delay: <?= $index * 0.1 ?>s">
                                    <div class="activity-icon-wrapper">
                                        <i class="<?= getActivityIcon($activityItem['description']) ?> activity-card-icon"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h6 class="activity-title"><?= e($activityItem['description']) ?></h6>
                                        <span class="activity-time">
                                            <i class="fas fa-clock"></i>
                                            <?= $activityItem['time'] ?>
                                        </span>
                                    </div>
                                    <div class="activity-glow"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="fas fa-bolt"></i>
                    Thao tác nhanh
                </h3>

                <div class="profile-quick-actions">
                    <?php if ($currentUser['VaiTroID'] >= ROLE_SELLER): ?>
                        <a href="/seller/posts/create" class="profile-quick-action">
                            <div class="profile-quick-action-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <h6 class="profile-quick-action-title">Tạo bài đăng mới</h6>
                        </a>

                        <a href="/seller/posts" class="profile-quick-action">
                            <div class="profile-quick-action-icon">
                                <i class="fas fa-list-alt"></i>
                            </div>
                            <h6 class="profile-quick-action-title">Quản lý bài đăng</h6>
                        </a>
                    <?php endif; ?>

                    <a href="/search" class="profile-quick-action">
                        <div class="profile-quick-action-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h6 class="profile-quick-action-title">Tìm kiếm phòng trọ</h6>
                    </a>

                    <a href="/profile/edit" class="profile-quick-action">
                        <div class="profile-quick-action-icon">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <h6 class="profile-quick-action-title">Chỉnh sửa hồ sơ</h6>
                    </a>

                    <a href="/profile/change-password" class="profile-quick-action">
                        <div class="profile-quick-action-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h6 class="profile-quick-action-title">Đổi mật khẩu</h6>
                    </a>

                    <a href="/notifications" class="profile-quick-action">
                        <div class="profile-quick-action-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h6 class="profile-quick-action-title">Thông báo</h6>
                    </a>
                </div>
            </div>
        </div>



        <!-- Favorites Tab -->
        <div class="tab-pane fade" id="favorites" role="tabpanel">
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="fas fa-heart"></i>
                    Bài đăng yêu thích (<?= count($favoritePosts) ?>)
                </h3>

                <?php if (empty($favoritePosts)): ?>
                    <div class="glass-container text-center py-5">
                        <div class="glass-icon mx-auto mb-3">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h5>Chưa có bài đăng yêu thích</h5>
                        <p class="text-muted mb-4">Hãy tìm kiếm và lưu những bài đăng bạn quan tâm</p>
                        <a href="/search" class="btn-glass-primary">
                            <i class="fas fa-search me-2"></i>
                            Tìm kiếm ngay
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($favoritePosts as $favoritePost): ?>
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="glass-container h-100">
                                    <div class="position-relative mb-3">
                                        <?php
                                        $postImage = !empty($favoritePost['AnhDaiDien'])
                                            ? $favoritePost['AnhDaiDien']
                                            : '/assets/images/default/no-image.png';
                                        ?>
                                        <img src="<?= e($postImage) ?>"
                                             class="w-100 rounded"
                                             style="height: 200px; object-fit: cover;"
                                             alt="<?= e($favoritePost['TieuDe']) ?>"
                                             onerror="this.src='/assets/images/default/no-image.png'">

                                        <div class="position-absolute top-0 end-0 m-2">
                                            <button class="btn-favorite favorited"
                                                    data-post-id="<?= $favoritePost['ID'] ?>"
                                                    onclick="toggleFavorite(<?= $favoritePost['ID'] ?>, this)"
                                                    title="Xóa khỏi yêu thích">
                                                <i class="fas fa-heart text-danger"></i>
                                                <span class="d-none d-md-inline">Đã yêu thích</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="p-3">
                                        <h6 class="mb-2">
                                            <a href="/post/<?= $favoritePost['ID'] ?>"
                                               class="text-decoration-none text-primary">
                                                <?= e(mb_substr($favoritePost['TieuDe'], 0, 60)) ?>
                                                <?= mb_strlen($favoritePost['TieuDe']) > 60 ? '...' : '' ?>
                                            </a>
                                        </h6>

                                        <div class="d-flex align-items-center text-muted mb-2">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <small>
                                                <?= e($favoritePost['DiaChi']) ?>
                                            </small>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-primary fw-bold">
                                                <?= number_format($favoritePost['Gia']) ?> VNĐ/tháng
                                            </span>
                                            <small class="text-muted">
                                                <i class="fas fa-expand-arrows-alt me-1"></i>
                                                <?= $favoritePost['DienTich'] ?>m²
                                            </small>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-heart me-1"></i>
                                                Yêu thích: <?= formatDate($favoritePost['favorite_date']) ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-eye me-1"></i>
                                                <?= number_format($favoritePost['LuotXem']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($favoritePosts) >= 10): ?>
                        <div class="text-center mt-4">
                            <a href="/favorites" class="btn-glass">
                                <i class="fas fa-heart me-2"></i>
                                Xem tất cả yêu thích
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Achievements Tab -->
        <div class="tab-pane fade" id="achievements" role="tabpanel">
            <div class="profile-section">
                <h3 class="profile-section-title">
                    <i class="fas fa-trophy"></i>
                    Thành tích & Huy hiệu
                </h3>

                <div class="profile-achievements">
                    <div class="achievements-grid">
                        <!-- First Post Achievement -->
                        <div class="achievement-badge <?= $stats['total_posts'] > 0 ? 'earned' : '' ?>">
                            <div class="achievement-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h6 class="achievement-title">Bài đăng đầu tiên</h6>
                            <p class="achievement-description">Đăng bài đầu tiên</p>
                        </div>

                        <!-- Email Verified Achievement -->
                        <div class="achievement-badge <?= !empty($currentUser['email_verified_at']) ? 'earned' : '' ?>">
                            <div class="achievement-icon">
                                <i class="fas fa-envelope-check"></i>
                            </div>
                            <h6 class="achievement-title">Email xác thực</h6>
                            <p class="achievement-description">Xác thực email</p>
                        </div>

                        <!-- Profile Complete Achievement -->
                        <div class="achievement-badge <?= $stats['profile_completion'] >= 80 ? 'earned' : '' ?>">
                            <div class="achievement-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h6 class="achievement-title">Hồ sơ hoàn thiện</h6>
                            <p class="achievement-description">Hoàn thiện 80% hồ sơ</p>
                        </div>

                        <!-- Seller Status Achievement -->
                        <div class="achievement-badge <?= $currentUser['VaiTroID'] >= ROLE_SELLER ? 'earned' : '' ?>">
                            <div class="achievement-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <h6 class="achievement-title">Trở thành Seller</h6>
                            <p class="achievement-description">Đăng ký seller thành công</p>
                        </div>

                        <!-- Popular Post Achievement -->
                        <div class="achievement-badge <?= $stats['total_views'] >= 1000 ? 'earned' : '' ?>">
                            <div class="achievement-icon">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h6 class="achievement-title">Bài đăng nổi bật</h6>
                            <p class="achievement-description">Đạt 1000+ lượt xem</p>
                        </div>

                        <!-- Long Time Member Achievement -->
                        <div class="achievement-badge <?= $stats['join_days'] >= 30 ? 'earned' : '' ?>">
                            <div class="achievement-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h6 class="achievement-title">Thành viên lâu năm</h6>
                            <p class="achievement-description">Tham gia 30+ ngày</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Tab - Modern Glass Morphism Design -->
        <div class="tab-pane fade" id="settings" role="tabpanel">
            <div class="settings-container">
                <div class="settings-header">
                    <h3 class="settings-title">
                        <i class="fas fa-cog settings-icon"></i>
                        Cài đặt tài khoản
                    </h3>
                    <p class="settings-subtitle">Quản lý thông tin cá nhân và tùy chỉnh trải nghiệm của bạn</p>
                </div>

                <!-- Settings Grid -->
                <div class="settings-grid">

                    <!-- Account Settings Card -->
                    <div class="settings-card" data-category="account">
                        <div class="settings-card-header">
                            <div class="settings-card-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="settings-card-title">
                                <h5>Tài khoản</h5>
                                <p>Quản lý thông tin cá nhân và bảo mật</p>
                            </div>
                        </div>

                        <div class="settings-card-content">
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <i class="fas fa-user-edit settings-item-icon"></i>
                                    <div class="settings-item-text">
                                        <h6>Chỉnh sửa hồ sơ</h6>
                                        <small>Cập nhật thông tin cá nhân</small>
                                    </div>
                                </div>
                                <a href="/profile/edit" class="settings-item-action">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>

                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <i class="fas fa-key settings-item-icon"></i>
                                    <div class="settings-item-text">
                                        <h6>Đổi mật khẩu</h6>
                                        <small>Cập nhật mật khẩu bảo mật</small>
                                    </div>
                                </div>
                                <a href="/profile/change-password" class="settings-item-action">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>

                            <?php if (empty($currentUser['email_verified_at'])): ?>
                            <div class="settings-item settings-item-warning">
                                <div class="settings-item-info">
                                    <i class="fas fa-envelope-check settings-item-icon"></i>
                                    <div class="settings-item-text">
                                        <h6>Xác thực email</h6>
                                        <small>Email chưa được xác thực</small>
                                    </div>
                                </div>
                                <a href="<?= app_url('/resend-verification') ?>" class="settings-item-action">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Seller Settings Card -->
                    <div class="settings-card" data-category="seller">
                        <div class="settings-card-header">
                            <div class="settings-card-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="settings-card-title">
                                <h5>Seller</h5>
                                <p>Quản lý tài khoản bán hàng</p>
                            </div>
                        </div>

                        <div class="settings-card-content">
                            <?php if ($currentUser['VaiTroID'] < ROLE_SELLER): ?>
                                <div class="settings-item settings-item-primary">
                                    <div class="settings-item-info">
                                        <i class="fas fa-store settings-item-icon"></i>
                                        <div class="settings-item-text">
                                            <h6>Đăng ký Seller</h6>
                                            <small>Trở thành người bán để đăng bài</small>
                                        </div>
                                    </div>
                                    <a href="/register-seller" class="settings-item-action">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>

                                <div class="settings-info-box">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Kiếm thu nhập từ hoa hồng 5% khi giao dịch thành công</span>
                                </div>
                            <?php else: ?>
                                <div class="settings-item">
                                    <div class="settings-item-info">
                                        <i class="fas fa-tachometer-alt settings-item-icon"></i>
                                        <div class="settings-item-text">
                                            <h6>Dashboard Seller</h6>
                                            <small>Xem thống kê và quản lý</small>
                                        </div>
                                    </div>
                                    <a href="/seller/dashboard" class="settings-item-action">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>

                                <div class="settings-item">
                                    <div class="settings-item-info">
                                        <i class="fas fa-list-alt settings-item-icon"></i>
                                        <div class="settings-item-text">
                                            <h6>Quản lý bài đăng</h6>
                                            <small>Tạo và chỉnh sửa bài đăng</small>
                                        </div>
                                    </div>
                                    <a href="/seller/posts" class="settings-item-action">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Appearance Settings Card -->
                    <div class="settings-card" data-category="appearance">
                        <div class="settings-card-header">
                            <div class="settings-card-icon">
                                <i class="fas fa-palette"></i>
                            </div>
                            <div class="settings-card-title">
                                <h5>Giao diện</h5>
                                <p>Tùy chỉnh theme và hiển thị</p>
                            </div>
                        </div>

                        <div class="settings-card-content">
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <i class="fas fa-moon settings-item-icon"></i>
                                    <div class="settings-item-text">
                                        <h6>Chế độ tối</h6>
                                        <small>Chuyển đổi giữa sáng và tối</small>
                                    </div>
                                </div>
                                <div class="settings-toggle-wrapper">
                                    <label class="settings-toggle">
                                        <input type="checkbox" class="settings-toggle-input" data-profile-theme-toggle>
                                        <span class="settings-toggle-slider">
                                            <span class="settings-toggle-button"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <i class="fas fa-bell settings-item-icon"></i>
                                    <div class="settings-item-text">
                                        <h6>Thông báo</h6>
                                        <small>Nhận thông báo qua email</small>
                                    </div>
                                </div>
                                <div class="settings-toggle-wrapper">
                                    <label class="settings-toggle">
                                        <input type="checkbox" class="settings-toggle-input" checked>
                                        <span class="settings-toggle-slider">
                                            <span class="settings-toggle-button"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Settings Card -->
                    <div class="settings-card" data-category="privacy">
                        <div class="settings-card-header">
                            <div class="settings-card-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="settings-card-title">
                                <h5>Quyền riêng tư</h5>
                                <p>Kiểm soát thông tin cá nhân</p>
                            </div>
                        </div>

                        <div class="settings-card-content">
                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <i class="fas fa-eye settings-item-icon"></i>
                                    <div class="settings-item-text">
                                        <h6>Hiển thị hồ sơ</h6>
                                        <small>Cho phép người khác xem hồ sơ</small>
                                    </div>
                                </div>
                                <div class="settings-toggle-wrapper">
                                    <label class="settings-toggle">
                                        <input type="checkbox" class="settings-toggle-input" checked>
                                        <span class="settings-toggle-slider">
                                            <span class="settings-toggle-button"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <i class="fas fa-phone settings-item-icon"></i>
                                    <div class="settings-item-text">
                                        <h6>Hiển thị số điện thoại</h6>
                                        <small>Cho phép liên hệ qua điện thoại</small>
                                    </div>
                                </div>
                                <div class="settings-toggle-wrapper">
                                    <label class="settings-toggle">
                                        <input type="checkbox" class="settings-toggle-input" checked>
                                        <span class="settings-toggle-slider">
                                            <span class="settings-toggle-button"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>



<!-- Profile Page JavaScript loaded via footer -->

<script>
// Global function for favorite toggle (copied from working home.php implementation)
function toggleFavorite(postId, buttonElement) {
    // Check if user is logged in
    <?php if (!$auth->isLoggedIn()): ?>
    showToast('Vui lòng đăng nhập để sử dụng tính năng này', 'info');
    window.location.href = '/login';
    return;
    <?php endif; ?>

    if (!buttonElement) {
        buttonElement = event?.target?.closest('button');
    }

    if (!buttonElement) {
        console.error('Cannot find button element for favorite toggle');
        return;
    }

    // Prevent multiple clicks
    if (buttonElement.disabled) {
        return;
    }

    // Show loading state
    const heartIcon = buttonElement.querySelector('i');
    const textSpan = buttonElement.querySelector('span');
    const originalHeartClasses = heartIcon.className;

    // Set loading state
    buttonElement.disabled = true;
    heartIcon.className = 'fas fa-spinner fa-spin';
    if (textSpan) textSpan.textContent = 'Đang xử lý...';

    window.Tro365Common.toggleFavorite(postId, function(data) {
        // Restore button state
        buttonElement.disabled = false;

        if (data.success && data.data) {
            // Update UI based on API response
            if (data.data.favorited) {
                // Show filled red heart
                heartIcon.className = 'fas fa-heart text-danger';
                buttonElement.classList.add('favorited');
                buttonElement.title = 'Xóa khỏi yêu thích';
                if (textSpan) textSpan.textContent = 'Đã yêu thích';
                showToast('Đã thêm vào danh sách yêu thích', 'success');
            } else {
                // Show empty heart
                heartIcon.className = 'far fa-heart';
                buttonElement.classList.remove('favorited');
                buttonElement.title = 'Thêm vào yêu thích';
                if (textSpan) textSpan.textContent = 'Yêu thích';

                // For profile favorites page, remove the card from list
                const card = buttonElement.closest('.col-lg-6, .col-xl-4');
                if (card) {
                    card.style.transition = 'opacity 0.3s ease';
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        // Update favorites count in title - find correct heading
                        const allHeadings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
                        let favoritesHeading = null;
                        for (let heading of allHeadings) {
                            if (heading.textContent.includes('Bài đăng yêu thích')) {
                                favoritesHeading = heading;
                                break;
                            }
                        }

                        if (favoritesHeading) {
                            const currentText = favoritesHeading.textContent;
                            const match = currentText.match(/\((\d+)\)/);
                            if (match) {
                                const newCount = Math.max(0, parseInt(match[1]) - 1);
                                favoritesHeading.innerHTML = favoritesHeading.innerHTML.replace(/\(\d+\)/, `(${newCount})`);
                            }
                        }
                        // Show empty state if no more favorites
                        const remainingCards = document.querySelectorAll('.col-lg-6, .col-xl-4');
                        if (remainingCards.length === 0) {
                            // Update counter to 0 when no more favorites - find correct heading
                            const allHeadings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
                            let favoritesHeading = null;
                            for (let heading of allHeadings) {
                                if (heading.textContent.includes('Bài đăng yêu thích')) {
                                    favoritesHeading = heading;
                                    break;
                                }
                            }

                            if (favoritesHeading) {
                                favoritesHeading.innerHTML = favoritesHeading.innerHTML.replace(/\(\d+\)/, '(0)');
                            }

                            // Show empty state without reload
                            const favoritesGrid = document.querySelector('.row.g-4');
                            if (favoritesGrid) {
                                favoritesGrid.innerHTML = `
                                    <div class="col-12">
                                        <div class="glass-card text-center py-5">
                                            <i class="fas fa-heart-broken fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Chưa có bài đăng yêu thích</h5>
                                            <p class="text-muted">Hãy tìm kiếm và lưu những bài đăng bạn quan tâm</p>
                                            <a href="/search" class="btn btn-primary">
                                                <i class="fas fa-search me-2"></i>Tìm kiếm ngay
                                            </a>
                                        </div>
                                    </div>
                                `;
                            }
                        }
                    }, 300);
                }
                showToast('Đã xóa khỏi danh sách yêu thích', 'info');
            }
        } else {
            // Restore original state on error
            heartIcon.className = originalHeartClasses;
            if (textSpan) {
                textSpan.textContent = buttonElement.classList.contains('favorited') ? 'Đã yêu thích' : 'Yêu thích';
            }

            // Show error message
            const errorMsg = data.message || 'Có lỗi xảy ra, vui lòng thử lại';
            showToast(errorMsg, 'error');
        }
    });
}

// Toast notification (unified with home.php and search.php)
function showToast(message, type = 'info', duration = 3000) {
    if (window.TroToast && typeof window.TroToast.show === 'function') {
        window.TroToast.show({ message, type, duration });
    } else {
        // Fallback minimal alert
        alert(message);
    }
}
</script>

<script>
// Handle #favorites anchor: switch to Favorites tab on load and on hash changes
(function() {
  function showFavoritesTab(scroll = true) {
    const favoritesTabBtn = document.getElementById('favorites-tab');
    const favoritesPane = document.getElementById('favorites');
    if (favoritesTabBtn) {
      try {
        // Prefer Bootstrap API if available
        if (window.bootstrap && window.bootstrap.Tab) {
          const tab = new window.bootstrap.Tab(favoritesTabBtn);
          tab.show();
        } else {
          // Fallback: simulate click
          favoritesTabBtn.click();
        }
      } catch (e) {
        favoritesTabBtn.click();
      }
    }
    if (scroll && favoritesPane) {
      favoritesPane.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function handleHash() {
    if (window.location.hash === '#favorites') {
      showFavoritesTab();
    }
  }

  document.addEventListener('DOMContentLoaded', handleHash);
  window.addEventListener('hashchange', handleHash);

  // Update URL hash when user clicks the Favorites tab
  document.addEventListener('DOMContentLoaded', function() {
    const favoritesTabBtn = document.getElementById('favorites-tab');
    if (!favoritesTabBtn) return;
    favoritesTabBtn.addEventListener('click', function() {
      if (window.location.hash !== '#favorites') {
        history.replaceState(null, '', '#favorites');
      }
    });
  });
})();
</script>


<?php include __DIR__ . '/../../../includes/layouts/client/footer.php'; ?>

<?php
// Debug panel removed - using unified footer DebugManager system
?>
