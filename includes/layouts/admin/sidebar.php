<?php
/**
 * Admin Sidebar Layout
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;

// Get current route for active menu highlighting
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$currentPath = parse_url($currentPath, PHP_URL_PATH);
$currentPath = trim($currentPath, '/');

// Get current user role for menu filtering
$auth = new Auth();
$currentUser = $auth->getCurrentUser();
$userRole = $currentUser['VaiTroID'] ?? 0;
$isAdmin = $userRole >= ROLE_ADMIN;
$isModerator = $userRole >= ROLE_MODERATOR;
?>

<div class="sidebar-header">
    <i class="fas fa-cogs me-2"></i>
    QUẢN TRỊ
</div>

<ul class="sidebar-menu">
    <?php if ($isAdmin): ?>
    <li>
        <a href="/admin" class="<?= $currentPath === 'admin' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt me-2"></i>
            Dashboard
        </a>
    </li>
    <?php endif; ?>

    <?php if ($isModerator): ?>
    <li>
        <a href="/admin/posts" class="<?= strpos($currentPath, 'admin/posts') === 0 ? 'active' : '' ?>">
            <i class="fas fa-list-alt me-2"></i>
            Duyệt bài đăng
        </a>
    </li>

    <li>
        <a href="/admin/users" class="<?= strpos($currentPath, 'admin/users') === 0 ? 'active' : '' ?>">
            <i class="fas fa-users me-2"></i>
            Quản lý người dùng
        </a>
    </li>

    <li>
        <a href="/admin/sellers" class="<?= strpos($currentPath, 'admin/sellers') === 0 ? 'active' : '' ?>">
            <i class="fas fa-store me-2"></i>
            Quản lý seller
        </a>
    </li>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <li>
        <a href="/admin/categories" class="<?= strpos($currentPath, 'admin/categories') === 0 ? 'active' : '' ?>">
            <i class="fas fa-tags me-2"></i>
            Danh mục
        </a>
    </li>

    <li>
        <a href="/admin/transactions" class="<?= strpos($currentPath, 'admin/transactions') === 0 ? 'active' : '' ?>">
            <i class="fas fa-handshake me-2"></i>
            Giao dịch
        </a>
    </li>

    <li>
        <a href="/admin/statistics" class="<?= strpos($currentPath, 'admin/statistics') === 0 ? 'active' : '' ?>">
            <i class="fas fa-chart-bar me-2"></i>
            Thống kê
        </a>
    </li>

    <li>
        <a href="/admin/settings" class="<?= strpos($currentPath, 'admin/settings') === 0 ? 'active' : '' ?>">
            <i class="fas fa-cog me-2"></i>
            Cài đặt
        </a>
    </li>
    <?php endif; ?>
</ul>

<!-- Sidebar styles moved to layouts.css -->
