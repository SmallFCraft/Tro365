<?php
/**
 * 403 Forbidden Error Page - Glass Morphism Design
 * Tro365 - Website thuê trọ
 */

// Set 403 response code
http_response_code(403);

// Include autoloader and configuration
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';

$pageTitle = '403 - Không có quyền truy cập';
$pageDescription = 'Bạn không có quyền truy cập vào trang này. Vui lòng đăng nhập hoặc liên hệ quản trị viên.';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? null;

include __DIR__ . '/../../includes/layouts/client/header.php';
?>

<!-- Additional CSS for Error Pages -->
<link href="<?= app_url('assets/css/components/error.css') ?>" rel="stylesheet">

<div class="container-fluid p-0">
    <div class="error-hero" style="min-height: 80vh;">
        <div class="error-container">
            <!-- Error Icon -->
            <div class="error-icon error-403">
                <div class="error-icon-container">
                    <i class="fas fa-shield-alt"></i>
                </div>
            </div>

            <!-- Error Number -->
            <h1 class="error-number error-403">403</h1>

            <!-- Error Content -->
            <div class="error-content error-403">
                <h2 class="error-title">Không có quyền truy cập</h2>
                <p class="error-description">
                    <?php if (!$isLoggedIn): ?>
                        Bạn cần đăng nhập để truy cập trang này. Vui lòng đăng nhập hoặc tạo tài khoản mới.
                    <?php else: ?>
                        Bạn không có quyền truy cập vào trang này. Vui lòng liên hệ quản trị viên nếu bạn cho rằng đây là lỗi.
                    <?php endif; ?>
                </p>

                <!-- Error Actions -->
                <div class="error-actions">
                    <?php if (!$isLoggedIn): ?>
                        <a href="/login" class="btn-error btn-error-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            Đăng nhập
                        </a>
                        <a href="/register" class="btn-error">
                            <i class="fas fa-user-plus"></i>
                            Đăng ký
                        </a>
                    <?php else: ?>
                        <a href="/" class="btn-error btn-error-primary">
                            <i class="fas fa-home"></i>
                            Về trang chủ
                        </a>
                        <a href="/contact" class="btn-error">
                            <i class="fas fa-headset"></i>
                            Liên hệ hỗ trợ
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>
