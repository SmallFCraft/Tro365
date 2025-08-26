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
<link href="/assets/css/components/error.css" rel="stylesheet">

<div class="container-fluid p-0">
    <div class="error-hero" style="min-height: 80vh;">
        <div class="error-container">
            <!-- Error Icon -->
            <div class="error-icon error-403">
                <i class="fas fa-shield-alt"></i>
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

            <!-- Error Suggestions -->
            <div class="error-suggestions">
                <?php if (!$isLoggedIn): ?>
                    <a href="/login" class="error-suggestion-item">
                        <div class="error-suggestion-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <h3 class="error-suggestion-title">Đăng nhập</h3>
                        <p class="error-suggestion-desc">Đăng nhập vào tài khoản của bạn</p>
                    </a>

                    <a href="/register" class="error-suggestion-item">
                        <div class="error-suggestion-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="error-suggestion-title">Đăng ký</h3>
                        <p class="error-suggestion-desc">Tạo tài khoản miễn phí</p>
                    </a>

                    <a href="/forgot-password" class="error-suggestion-item">
                        <div class="error-suggestion-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h3 class="error-suggestion-title">Quên mật khẩu</h3>
                        <p class="error-suggestion-desc">Khôi phục mật khẩu</p>
                    </a>
                <?php else: ?>
                    <a href="/profile" class="error-suggestion-item">
                        <div class="error-suggestion-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="error-suggestion-title">Hồ sơ</h3>
                        <p class="error-suggestion-desc">Xem thông tin cá nhân</p>
                    </a>

                    <a href="/search" class="error-suggestion-item">
                        <div class="error-suggestion-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h3 class="error-suggestion-title">Phòng trọ</h3>
                        <p class="error-suggestion-desc">Tìm phòng trọ phù hợp</p>
                    </a>

                    <a href="/contact" class="error-suggestion-item">
                        <div class="error-suggestion-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3 class="error-suggestion-title">Hỗ trợ</h3>
                        <p class="error-suggestion-desc">Liên hệ để được hỗ trợ</p>
                    </a>
                <?php endif; ?>

                <a href="/" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3 class="error-suggestion-title">Trang chủ</h3>
                    <p class="error-suggestion-desc">Quay về trang chủ</p>
                </a>

                <a href="/about" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3 class="error-suggestion-title">Về chúng tôi</h3>
                    <p class="error-suggestion-desc">Tìm hiểu về Tro365</p>
                </a>

                <a href="/search" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="error-suggestion-title">Tìm kiếm</h3>
                    <p class="error-suggestion-desc">Tìm kiếm phòng trọ</p>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Additional Information Panel -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="glass-card">
                <div class="text-center mb-4">
                    <div class="glass-icon-lg mx-auto mb-3">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3>Thông tin về quyền truy cập</h3>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="glass-card-sm">
                            <h5><i class="fas fa-user text-primary me-2"></i>Người dùng</h5>
                            <p class="mb-0">Có thể xem phòng trọ, tìm kiếm và liên hệ</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-card-sm">
                            <h5><i class="fas fa-store text-success me-2"></i>Người bán</h5>
                            <p class="mb-0">Có thể đăng tin, quản lý bài đăng</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-card-sm">
                            <h5><i class="fas fa-headset text-warning me-2"></i>Hỗ trợ</h5>
                            <p class="mb-0">Hỗ trợ khách hàng và người bán</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-card-sm">
                            <h5><i class="fas fa-shield-alt text-danger me-2"></i>Quản trị</h5>
                            <p class="mb-0">Quản lý toàn bộ hệ thống</p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted">
                        Nếu bạn cần nâng cấp quyền truy cập, vui lòng liên hệ với chúng tôi.
                    </p>
                    <a href="/contact" class="btn-glass btn-glass-primary">
                        <i class="fas fa-envelope me-2"></i>
                        Liên hệ ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>
