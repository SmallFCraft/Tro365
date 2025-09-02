<?php
use Tro365\Core\Auth;
use Tro365\Models\Post;
use Tro365\Core\Database;

// Initialize required objects
$auth = new \Tro365\Core\Auth();
$postService = new \Tro365\Models\Post();

// Get current user
$currentUser = $auth->getCurrentUser();

// Get favorites count for logged in users
$favoritesCount = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $db = \Tro365\Core\Database::getInstance();
        $favoritesResult = $db->selectOne("
            SELECT COUNT(*) as count
            FROM YeuThich yt
            INNER JOIN BaiDang bd ON yt.BaiDangID = bd.ID
            WHERE yt.KhachHangID = :userId
            AND bd.TrangThai = :status
        ", [
            'userId' => $_SESSION['user_id'],
            'status' => POST_STATUS_APPROVED
        ]);
        $favoritesCount = $favoritesResult['count'] ?? 0;
    } catch (Exception $e) {
        // Silently fail, keep count as 0
    }
}
?>
<!DOCTYPE html>
<html lang="vi" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= $pageTitle ?? 'Trang chủ' ?> - <?= getWebsiteName() ?></title>
    <meta name="description" content="<?= $pageDescription ?? getMetaDescription() ?>">
    <meta name="keywords" content="<?= $pageKeywords ?? getMetaKeywords() ?>">
    <meta name="author" content="<?= getWebsiteName() ?>">
    <meta name="theme-color" content="#0d6efd" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1a1d29" media="(prefers-color-scheme: dark)">

    <!-- Preload critical resources for LCP optimization (only on pages that use hero) -->
    <?php if (!empty($hasHeroSection)) : ?>
    <link rel="preload" href="/assets/images/hero_section.jpg" as="image" fetchpriority="high">
    <?php endif; ?>

    <!-- Preload critical CSS and fonts -->
    <link rel="preload" href="/assets/css/client/layouts.css" as="style">
    <link rel="preload" href="/assets/css/client/main.css" as="style">


    <!-- Preload critical JavaScript -->
    <link rel="preload" href="/assets/js/client/navigation.js" as="script">

    <?php if (getGoogleSearchConsole()): ?>
    <meta name="google-site-verification" content="<?= getGoogleSearchConsole() ?>">
    <?php endif; ?>

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= $pageTitle ?? 'Trang chủ' ?> - <?= getWebsiteName() ?>">
    <meta property="og:description" content="<?= $pageDescription ?? getMetaDescription() ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:image" content="<?= isset($pageImage) ? (strpos($pageImage, 'http') === 0 ? $pageImage : app_url($pageImage)) : app_url('/assets/images/logo/logo-og.png') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/png">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $pageTitle ?? 'Trang chủ' ?> - <?= getWebsiteName() ?>">
    <meta name="twitter:description" content="<?= $pageDescription ?? getMetaDescription() ?>">
    <meta name="twitter:image" content="<?= isset($pageImage) ? (strpos($pageImage, 'http') === 0 ? $pageImage : app_url($pageImage)) : app_url('/assets/images/logo/logo-og.png') ?>">

    <!-- Resource Hints for Performance -->
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//code.jquery.com">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Preload Critical Resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
    <link rel="preload" href="/assets/css/client/layouts.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-brands-400.woff2" as="font" type="font/woff2" crossorigin>

    <!-- Bootstrap CSS (async load with preload swap) -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>

    <!-- Google Fonts (use display=swap to avoid FOIT) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Resource Hints: Preconnect to critical origins -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Font Awesome (defer to after first paint) -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"></noscript>

    <!-- Layout CSS (critical) -->
    <link href="/assets/css/client/layouts.css" rel="stylesheet">

    <!-- Glass Morphism components (async) -->
    <link rel="preload" href="/assets/css/client/glass-morphism.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="/assets/css/client/glass-morphism.css" rel="stylesheet"></noscript>

    <!-- Non-critical local CSS (async) -->
    <link rel="preload" href="/assets/css/components/common.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="/assets/css/components/common.css" rel="stylesheet"></noscript>
    <link rel="preload" href="/assets/css/client/main.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="/assets/css/client/main.css" rel="stylesheet"></noscript>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/logo/favicon.ico">
    <link rel="apple-touch-icon" href="/assets/images/logo/apple-touch-icon.png">
    <!-- TODO: Create favicon-32x32.png and favicon-16x16.png from favicon.ico -->
    <!-- <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/logo/favicon-32x32.png"> -->
    <!-- <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/logo/favicon-16x16.png"> -->

    <!-- Google Analytics -->
    <?php if (getGoogleAnalyticsId()): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= getGoogleAnalyticsId() ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= getGoogleAnalyticsId() ?>');
    </script>
    <?php endif; ?>

    <!-- Facebook Pixel -->
    <?php if (getFacebookPixelId()): ?>
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?= getFacebookPixelId() ?>');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id=<?= getFacebookPixelId() ?>&ev=PageView&noscript=1"/>
    </noscript>
    <?php endif; ?>

    <!-- Additional CSS for specific pages -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?= $css ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Global Performance Optimization -->
    <?php
    // Apply global performance optimizations
    if (class_exists('Tro365\Services\PerformanceOptimizationService')) {
        try {
            $globalPerfService = \Tro365\Services\PerformanceOptimizationService::getInstance();
            $lcpOptimization = $globalPerfService->getLCPOptimization();

            // Inject critical CSS if available
            if (!empty($lcpOptimization['critical_css'])) {
                echo $lcpOptimization['critical_css'];
            }
        } catch (Exception $e) {
            // Fail silently in production
            if (isDebugModeEnabled()) {
                error_log("Global performance optimization failed: " . $e->getMessage());
            }
        }
    }
    ?>

    <?php
    /*
    // TODO: Implement DebugManager class
    // Initialize Debug Manager for client pages
    if (isDebugModeEnabled()) {
        $debugManager = \Tro365\DebugManager::getInstance();
        $debugManager->addDebugInfo('page', 'type', 'client');
        $debugManager->addDebugInfo('page', 'title', $pageTitle ?? 'Unknown');
        $debugManager->addDebugInfo('page', 'template', basename($_SERVER['SCRIPT_NAME'] ?? ''));
    }
    */
    ?>

    <!-- Global Debug Configuration -->
    <script>
        // Set global debug flag based on PHP debug setting
        window.TRO365_DEBUG = <?= isDebugModeEnabled() ? 'true' : 'false' ?>;

        // Override console.log if debug is disabled
        if (!window.TRO365_DEBUG) {
            const originalConsole = {
                log: console.log,
                warn: console.warn,
                error: console.error,
                info: console.info
            };

            // Only disable log and info, keep warn and error for important messages
            console.log = function() {};
            console.info = function() {};

            // Keep warn and error for important debugging
            // console.warn = function() {};
            // console.error = function() {};
        }
    </script>
    
    <!-- Modern Assets Integration (AssetManager) -->
    <?php
    $am = new \Tro365\Assets\AssetManager(app_url(''));
    $am->addMetaTags(['csrf' => csrf_token()]);
    echo $am->renderHead();
    ?>

    <!-- Custom CSS -->
    <?php if (isset($customCSS)): ?>
        <style><?= $customCSS ?></style>
    <?php endif; ?>
</head>
<body<?= isset($bodyClass) ? ' class="' . htmlspecialchars($bodyClass) . '"' : '' ?>>

<?php
/**
 * Client Header/Navigation Layout
 * Tro365 - Website thuê trọ
 */


?>

<!-- Modern Navigation -->
<nav class="tro365-navbar" id="mainNavbar">
    <div class="navbar-container">
        <!-- Brand -->
        <a class="navbar-brand" href="/" aria-label="<?= getWebsiteName() ?> - Trang chủ">
            <div class="brand-logo">
                <i class="fas fa-home" aria-hidden="true"></i>
            </div>
            <span class="brand-text"><?= getWebsiteName() ?></span>
        </a>

        <!-- Desktop Navigation -->
        <div class="navbar-nav-desktop">
            <ul class="nav-links" role="menubar">
                <li class="nav-item" role="none">
                    <a class="nav-link <?= ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '') ? 'active' : '' ?>"
                       href="/" role="menuitem" aria-current="<?= ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '') ? 'page' : 'false' ?>">
                        <i class="fas fa-home" aria-hidden="true"></i>
                        <span>Trang chủ</span>
                    </a>
                </li>
                <li class="nav-item" role="none">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/search') === 0 ? 'active' : '' ?>"
                       href="/search" role="menuitem" aria-current="<?= strpos($_SERVER['REQUEST_URI'], '/search') === 0 ? 'page' : 'false' ?>">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>Tìm kiếm</span>
                    </a>
                </li>
                <li class="nav-item" role="none">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/about') === 0 ? 'active' : '' ?>"
                       href="/about" role="menuitem" aria-current="<?= strpos($_SERVER['REQUEST_URI'], '/about') === 0 ? 'page' : 'false' ?>">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <span>Giới thiệu</span>
                    </a>
                </li>
                <li class="nav-item" role="none">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/contact') === 0 ? 'active' : '' ?>"
                       href="/contact" role="menuitem" aria-current="<?= strpos($_SERVER['REQUEST_URI'], '/contact') === 0 ? 'page' : 'false' ?>">
                        <i class="fas fa-phone" aria-hidden="true"></i>
                        <span>Liên hệ</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="navbar-actions">
            <!-- Theme Toggle -->
            <button class="action-btn theme-toggle" id="themeToggle" aria-label="Chuyển đổi chế độ sáng/tối" title="Chuyển đổi theme">
                <i class="fas fa-sun light-icon" aria-hidden="true"></i>
                <i class="fas fa-moon dark-icon" aria-hidden="true"></i>
            </button>

            <!-- Search Toggle -->
            <button class="action-btn search-toggle" id="searchToggle" aria-label="Mở tìm kiếm" title="Tìm kiếm nhanh">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>

            <!-- Notifications (for logged in users) -->
            <?php if ($auth->isLoggedIn()): ?>
            <?php
            // Get unread notifications count
            $unreadNotificationsCount = 0;
            try {
                $db = \Tro365\Core\Database::getInstance();
                $notificationResult = $db->selectOne("
                    SELECT COUNT(*) as count
                    FROM ThongBao
                    WHERE NguoiNhanID = :userId
                    AND DaDoc = 0
                ", [
                    'userId' => $currentUser['ID']
                ]);
                $unreadNotificationsCount = $notificationResult['count'] ?? 0;
            } catch (Exception $e) {
                // Silently fail, keep count as 0
            }
            ?>
            <div class="notification-wrapper">
                <button class="action-btn notification-toggle" id="notificationToggle" aria-label="Thông báo" title="Thông báo">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <span class="notification-badge" id="notificationBadge"><?= $unreadNotificationsCount ?></span>
                    <?php else: ?>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    <?php endif; ?>
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h6>Thông báo</h6>
                        <button class="btn-mark-all-read" id="markAllRead">Đánh dấu đã đọc</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <!-- Notifications will be loaded via JavaScript -->
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Không có thông báo mới</p>
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="/notifications" class="btn-view-all">Xem tất cả</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- User Menu -->
            <?php if ($auth->isLoggedIn()): ?>
            <div class="user-menu-wrapper">
                <button class="user-menu-toggle" id="userMenuToggle" aria-label="Menu người dùng" aria-expanded="false">
                    <div class="user-avatar-container">
                        <div class="user-avatar">
                            <?php if (!empty($currentUser['AnhDaiDien'])): ?>
                                <img src="<?= e($currentUser['AnhDaiDien']) ?>" alt="<?= e($currentUser['HoTen']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="user-status online"></div>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= e(truncateText($currentUser['HoTen'] ?? 'User', 15)) ?></span>
                        <span class="user-role"><?= e($currentUser['TenVT'] ?? 'Thành viên') ?></span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow" aria-hidden="true"></i>
                </button>

                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-header">
                        <div class="user-avatar-large">
                            <?php if (!empty($currentUser['AnhDaiDien'])): ?>
                                <img src="<?= e($currentUser['AnhDaiDien']) ?>" alt="<?= e($currentUser['HoTen']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <h6><?= e($currentUser['HoTen'] ?? 'User') ?></h6>
                            <p><?= e($currentUser['Email'] ?? '') ?></p>
                            <span class="user-badge"><?= e($currentUser['TenVT'] ?? 'Thành viên') ?></span>
                        </div>
                    </div>

                    <div class="user-dropdown-body">
                        <div class="dropdown-section">
                            <h6 class="section-title">Tài khoản</h6>
                            <a href="/profile" class="dropdown-item">
                                <i class="fas fa-user-circle" aria-hidden="true"></i>
                                <span>Hồ sơ cá nhân</span>
                            </a>
                            <a href="/profile/edit" class="dropdown-item">
                                <i class="fas fa-edit" aria-hidden="true"></i>
                                <span>Chỉnh sửa thông tin</span>
                            </a>
                            <a href="/profile/change-password" class="dropdown-item">
                                <i class="fas fa-key" aria-hidden="true"></i>
                                <span>Đổi mật khẩu</span>
                            </a>
                        </div>

                        <div class="dropdown-section">
                            <h6 class="section-title">Hoạt động</h6>
                            <a href="/profile#favorites" class="dropdown-item">
                                <i class="fas fa-heart" aria-hidden="true"></i>
                                <span>Yêu thích</span>
                                <?php if ($favoritesCount > 0): ?>
                                    <span class="item-count"><?= number_format($favoritesCount) ?></span>
                                <?php endif; ?>
                            </a>

                            <a href="/profile/history" class="dropdown-item">
                                <i class="fas fa-history" aria-hidden="true"></i>
                                <span>Lịch sử xem</span>
                            </a>
                        </div>

                        <?php if ($auth->hasRole(ROLE_SELLER)): ?>
                        <div class="dropdown-section">
                            <h6 class="section-title">Seller</h6>
                            <a href="/seller" class="dropdown-item">
                                <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="/seller/posts/create" class="dropdown-item highlight">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                                <span>Đăng bài mới</span>
                            </a>
                            <a href="/seller/posts" class="dropdown-item">
                                <i class="fas fa-list" aria-hidden="true"></i>
                                <span>Quản lý bài đăng</span>
                                <?php
                                // Get user's posts count
                                $userPostsCount = 0;
                                if (isset($currentUser['ID'])) {
                                    $userPostsCount = $postService->count(['user_id' => $currentUser['ID']]);
                                }
                                ?>
                                <?php if ($userPostsCount > 0): ?>
                                    <span class="item-count"><?= number_format($userPostsCount) ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($auth->hasRole(ROLE_ADMIN)): ?>
                        <div class="dropdown-section">
                            <h6 class="section-title">Quản trị</h6>
                            <a href="/admin" class="dropdown-item admin">
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                <span>Admin Panel</span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="user-dropdown-footer">
                        <a href="/logout" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                            <span>Đăng xuất</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Guest Actions -->
            <div class="guest-actions">
                <a href="/login" class="btn-glass-secondary btn-sm">
                    <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                    <span>Đăng nhập</span>
                </a>
                <a href="/register" class="btn btn-primary btn-sm">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    <span>Đăng ký</span>
                </a>
            </div>
            <?php endif; ?>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Menu di động" aria-expanded="false">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="bottom-nav-container">
        <a href="/" class="bottom-nav-item <?= ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '') ? 'active' : '' ?>" aria-label="Trang chủ">
            <i class="fas fa-home" aria-hidden="true"></i>
            <span>Trang chủ</span>
        </a>
        <a href="/search" class="bottom-nav-item <?= strpos($_SERVER['REQUEST_URI'], '/search') === 0 ? 'active' : '' ?>" aria-label="Tìm kiếm">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span>Tìm kiếm</span>
        </a>
        <?php if ($auth->isLoggedIn()): ?>
            <?php if ($auth->hasRole(ROLE_SELLER)): ?>
                <a href="/seller/posts/create" class="bottom-nav-item create-post" aria-label="Đăng bài">
                    <div class="create-post-btn">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                    </div>
                    <span>Đăng bài</span>
                </a>
            <?php else: ?>
                <button class="bottom-nav-item" id="mobileQuickSearch" aria-label="Tìm kiếm nhanh">
                    <i class="fas fa-filter" aria-hidden="true"></i>
                    <span>Bộ lọc</span>
                </button>
            <?php endif; ?>
            <a href="/profile#favorites" class="bottom-nav-item" aria-label="Yêu thích">
                <i class="fas fa-heart" aria-hidden="true"></i>
                <span>Yêu thích</span>
                <?php if ($favoritesCount > 0): ?>
                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill" style="font-size: 0.6rem;">
                        <?= $favoritesCount > 99 ? '99+' : $favoritesCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="/profile" class="bottom-nav-item" aria-label="Tài khoản">
                <div class="bottom-nav-avatar">
                    <?php if (!empty($currentUser['AnhDaiDien'])): ?>
                        <img src="<?= e($currentUser['AnhDaiDien']) ?>" alt="<?= e($currentUser['HoTen']) ?>" loading="lazy">
                    <?php else: ?>
                        <i class="fas fa-user" aria-hidden="true"></i>
                    <?php endif; ?>
                </div>
                <span>Tài khoản</span>
            </a>
        <?php else: ?>
            <button class="bottom-nav-item" id="mobileQuickSearch" aria-label="Tìm kiếm nhanh">
                <i class="fas fa-filter" aria-hidden="true"></i>
                <span>Bộ lọc</span>
            </button>
            <a href="/about" class="bottom-nav-item" aria-label="Giới thiệu">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span>Giới thiệu</span>
            </a>
            <a href="/login" class="bottom-nav-item" aria-label="Đăng nhập">
                <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                <span>Đăng nhập</span>
            </a>
        <?php endif; ?>
    </div>
</nav>

<!-- Advanced Search Overlay -->
<div class="search-overlay" id="searchOverlay">
    <div class="search-overlay-content">
        <div class="search-header">
            <h3>
                <i class="fas fa-search" aria-hidden="true"></i>
                Tìm kiếm nâng cao
            </h3>
            <button class="search-close" id="searchClose" aria-label="Đóng tìm kiếm">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <form class="advanced-search-form" id="advancedSearchForm">
            <div class="search-section">
                <h4>Từ khóa</h4>
                <div class="search-input-wrapper">
                    <input type="text" name="keyword" class="search-input" placeholder="Nhập từ khóa tìm kiếm..." autocomplete="off">
                    <button type="button" class="voice-search" id="voiceSearch" aria-label="Tìm kiếm bằng giọng nói" title="Tìm kiếm bằng giọng nói">
                        <i class="fas fa-microphone" aria-hidden="true"></i>
                    </button>
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>
            </div>

            <div class="search-section">
                <h4>Vị trí</h4>
                <div class="location-inputs">
                    <select name="province" class="form-select" id="searchProvince">
                        <option value="">Chọn tỉnh/thành</option>
                    </select>
                    <select name="district" class="form-select" id="searchDistrict" disabled>
                        <option value="">Chọn quận/huyện</option>
                    </select>
                    <select name="ward" class="form-select" id="searchWard" disabled>
                        <option value="">Chọn phường/xã</option>
                    </select>
                </div>
                <button type="button" class="location-detect" id="detectLocation" aria-label="Phát hiện vị trí hiện tại">
                    <i class="fas fa-location-arrow" aria-hidden="true"></i>
                    Vị trí hiện tại
                </button>
            </div>

            <div class="search-section">
                <h4>Loại phòng</h4>
                <div class="category-grid">
                    <label class="category-item">
                        <input type="radio" name="category" value="">
                        <span class="category-icon"><i class="fas fa-th-large" aria-hidden="true"></i></span>
                        <span class="category-text">Tất cả</span>
                    </label>
                    <label class="category-item">
                        <input type="radio" name="category" value="1">
                        <span class="category-icon"><i class="fas fa-bed" aria-hidden="true"></i></span>
                        <span class="category-text">Phòng trọ</span>
                    </label>
                    <label class="category-item">
                        <input type="radio" name="category" value="2">
                        <span class="category-icon"><i class="fas fa-building" aria-hidden="true"></i></span>
                        <span class="category-text">Căn hộ mini</span>
                    </label>
                    <label class="category-item">
                        <input type="radio" name="category" value="3">
                        <span class="category-icon"><i class="fas fa-home" aria-hidden="true"></i></span>
                        <span class="category-text">Nhà nguyên căn</span>
                    </label>
                    <label class="category-item">
                        <input type="radio" name="category" value="4">
                        <span class="category-icon"><i class="fas fa-school" aria-hidden="true"></i></span>
                        <span class="category-text">Ký túc xá</span>
                    </label>
                    <label class="category-item">
                        <input type="radio" name="category" value="5">
                        <span class="category-icon"><i class="fas fa-heart" aria-hidden="true"></i></span>
                        <span class="category-text">Homestay</span>
                    </label>
                </div>
            </div>

            <div class="search-section">
                <h4>Khoảng giá</h4>
                <div class="price-range">
                    <div class="price-inputs">
                        <input type="number" name="price_from" class="price-input" placeholder="Từ" min="0">
                        <span class="price-separator">-</span>
                        <input type="number" name="price_to" class="price-input" placeholder="Đến" min="0">
                    </div>
                    <div class="price-slider">
                        <input type="range" name="price_range" class="range-slider" min="0" max="20000000" step="500000" value="0">
                        <div class="range-labels">
                            <span>0đ</span>
                            <span>20tr</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="search-section">
                <h4>Diện tích</h4>
                <div class="area-inputs">
                    <input type="number" name="area_from" class="form-input" placeholder="Từ (m²)" min="0">
                    <input type="number" name="area_to" class="form-input" placeholder="Đến (m²)" min="0">
                </div>
            </div>

            <!-- Tiện ích section - Tạm thời comment để chờ cập nhật CSDL -->
            <!--
            <div class="search-section">
                <h4>Tiện ích</h4>
                <div class="amenities-grid">
                    <label class="amenity-item">
                        <input type="checkbox" name="amenities[]" value="wifi">
                        <span class="amenity-icon"><i class="fas fa-wifi" aria-hidden="true"></i></span>
                        <span class="amenity-text">WiFi</span>
                    </label>
                    <label class="amenity-item">
                        <input type="checkbox" name="amenities[]" value="parking">
                        <span class="amenity-icon"><i class="fas fa-car" aria-hidden="true"></i></span>
                        <span class="amenity-text">Chỗ đậu xe</span>
                    </label>
                    <label class="amenity-item">
                        <input type="checkbox" name="amenities[]" value="ac">
                        <span class="amenity-icon"><i class="fas fa-snowflake" aria-hidden="true"></i></span>
                        <span class="amenity-text">Điều hòa</span>
                    </label>
                    <label class="amenity-item">
                        <input type="checkbox" name="amenities[]" value="kitchen">
                        <span class="amenity-icon"><i class="fas fa-utensils" aria-hidden="true"></i></span>
                        <span class="amenity-text">Bếp</span>
                    </label>
                    <label class="amenity-item">
                        <input type="checkbox" name="amenities[]" value="washing">
                        <span class="amenity-icon"><i class="fas fa-tshirt" aria-hidden="true"></i></span>
                        <span class="amenity-text">Máy giặt</span>
                    </label>
                    <label class="amenity-item">
                        <input type="checkbox" name="amenities[]" value="security">
                        <span class="amenity-icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
                        <span class="amenity-text">An ninh</span>
                    </label>
                </div>
            </div>
            -->

            <div class="search-actions">
                <button type="button" class="btn btn-outline-secondary" id="resetSearch">
                    <i class="fas fa-undo" aria-hidden="true"></i>
                    Đặt lại
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    Tìm kiếm
                </button>
            </div>
        </form>
    </div>
</div>



<!-- Flash Messages -->
<?php /*
$flash = getFlashMessage();
if ($flash): 
?>
    <div class="container mt-3">
        <div class="alert alert-<?= $flash['type'] === MSG_SUCCESS ? 'success' : ($flash['type'] === MSG_WARNING ? 'warning' : 'danger') ?> alert-dismissible fade show" role="alert">
            <?php if ($flash['type'] === MSG_SUCCESS): ?>
                <i class="fas fa-check-circle me-2"></i>
            <?php elseif ($flash['type'] === MSG_WARNING): ?>
                <i class="fas fa-exclamation-triangle me-2"></i>
            <?php else: ?>
                <i class="fas fa-exclamation-circle me-2"></i>
            <?php endif; ?>
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif;
*/ ?>

<!-- Navigation JavaScript - Deferred for Performance -->
<script src="/assets/js/client/navigation.js" defer></script>

<!-- Image Fallback JavaScript - Deferred for Performance -->
<script src="/assets/js/global/image-fallback.js" defer></script>

<!-- Lazy Loading JavaScript - Deferred for Performance -->
<script src="/assets/js/client/lazy-loading.js" defer></script>

<!-- Performance Optimizations Applied -->
<script>
    // Initialize Tro365Common for caching
    window.Tro365Common = window.Tro365Common || { _cache: { provinces: null, districts: {}, wards: {} } };
</script>

<!-- Initialize Navigation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof ModernNavigation !== 'undefined') {
            window.modernNav = new ModernNavigation();
        }
    });
</script>
