<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - <?= getWebsiteName() ?></title>
    <meta name="description" content="Admin Panel - <?= getWebsiteName() ?>">
    
    <!-- Resource Hints: Preconnect to critical origins -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- Common Components CSS -->
    <link href="/assets/css/components/common.css" rel="stylesheet">

    <!-- Admin Layout CSS (Header, Sidebar, Footer) -->
    <link href="/assets/css/admin/layouts.css" rel="stylesheet">

    <!-- Admin CSS -->
    <link href="/assets/css/admin/admin.css" rel="stylesheet">

    <!-- Modern Assets Integration (AssetManager) -->
    <?php
    $am = new \Tro365\Assets\AssetManager(app_url(''));
    $am->addMetaTags(['csrf' => csrf_token()]);
    echo $am->renderHead();
    ?>

    <!-- Additional CSS for specific pages -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?= $css ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    // Initialize Debug Manager for admin pages
    if (isDebugModeEnabled()) {
        $debugManager = \Tro365\DebugManager::getInstance();
        $debugManager->addDebugInfo('page', 'type', 'admin');
        $debugManager->addDebugInfo('page', 'title', $pageTitle ?? 'Unknown');
        $debugManager->addDebugInfo('page', 'template', basename($_SERVER['SCRIPT_NAME'] ?? ''));
        $debugManager->addDebugInfo('admin', 'user_id', $_SESSION['user_id'] ?? 'guest');
        $debugManager->addDebugInfo('admin', 'user_role', $_SESSION['user_role'] ?? 'none');
    }
    ?>
    
    <!-- Custom CSS -->
    <?php if (isset($customCSS)): ?>
        <style><?= $customCSS ?></style>
    <?php endif; ?>
</head>
<body>

<?php
/**
 * Admin Header/Navigation Layout
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;

$auth = new Auth();
$currentUser = $auth->getCurrentUser();
?>

<!-- Admin Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold pulse-hover" href="/admin">
            <i class="fas fa-shield-alt me-2"></i><?= getWebsiteName() ?> Admin
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin') === 0 && $_SERVER['REQUEST_URI'] === '/admin' ? 'active' : '' ?>" href="/admin">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/users') === 0 ? 'active' : '' ?>" href="/admin/users">
                        <i class="fas fa-users me-2"></i>Người dùng
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/sellers') === 0 ? 'active' : '' ?>" href="/admin/sellers">
                        <i class="fas fa-store me-2"></i>Seller
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/posts') === 0 ? 'active' : '' ?>" href="/admin/posts">
                        <i class="fas fa-list-alt me-2"></i>Bài đăng
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/categories') === 0 ? 'active' : '' ?>" href="/admin/categories">
                        <i class="fas fa-tags me-2"></i>Danh mục
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/transactions') === 0 ? 'active' : '' ?>" href="/admin/transactions">
                        <i class="fas fa-handshake me-2"></i>Giao dịch
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/statistics') === 0 ? 'active' : '' ?>" href="/admin/statistics">
                        <i class="fas fa-chart-bar me-2"></i>Thống kê
                    </a>
                </li>   
            </ul>

            <ul class="navbar-nav me-3">
                <li class="nav-item">
                    <a class="nav-link" href="/" target="_blank" title="Xem website" data-bs-toggle="tooltip">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin/settings" title="Cài đặt" data-bs-toggle="tooltip">
                        <i class="fas fa-cog"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/logout" title="Đăng xuất" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </li>
            </ul>

            <!-- User Profile Dropdown -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-profile" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="d-flex align-items-center">
                            <?php if ($currentUser['AnhDaiDien']): ?>
                                <img src="<?= e($currentUser['AnhDaiDien']) ?>"
                                     alt="Avatar"
                                     class="rounded-circle me-2 user-avatar"
                                     style="width: 36px; height: 36px; object-fit: cover; border: 2px solid rgba(255,255,255,0.4); box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                            <?php else: ?>
                                <div class="user-avatar-placeholder me-2">
                                    <i class="fas fa-user-circle" style="font-size: 2rem; color: rgba(255,255,255,0.8);"></i>
                                </div>
                            <?php endif; ?>
                            <div class="user-info">
                                <span class="fw-semibold text-white"><?= e($currentUser['HoTen']) ?></span>
                                <br>
                                <small class="text-white-50">
                                    <?php
                                    $roleNames = [
                                        1 => 'User',
                                        2 => 'Seller',
                                        3 => 'Supporter',
                                        4 => 'Moderator',
                                        5 => 'Admin'
                                    ];
                                    echo $roleNames[$currentUser['VaiTroID']] ?? 'Unknown';
                                    ?>
                                </small>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                        <li class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <?php if ($currentUser['AnhDaiDien']): ?>
                                    <img src="<?= e($currentUser['AnhDaiDien']) ?>"
                                         alt="Avatar"
                                         class="rounded-circle me-2"
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle me-2 text-muted" style="font-size: 2.5rem;"></i>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-semibold"><?= e($currentUser['HoTen']) ?></div>
                                    <small class="text-muted"><?= e($currentUser['Email']) ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/profile">
                                <i class="fas fa-user-circle me-2"></i>Thông tin cá nhân
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/profile/change-password">
                                <i class="fas fa-key me-2"></i>Đổi mật khẩu
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/profile/settings">
                                <i class="fas fa-cog me-2"></i>Cài đặt tài khoản
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="/logout" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')">
                                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Wrapper -->
<div class="main-content">

<!-- Flash Messages -->
<?php 
$flash = getFlashMessage();
if ($flash): 
?>
    <div class="container-fluid mt-3">
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
<?php endif; ?>
