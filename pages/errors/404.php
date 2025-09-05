<?php
/**
 * 404 Error Page - Glass Morphism Design
 * Tro365 - Website thuê trọ
 */

// Set 404 response code
http_response_code(404);

// Include autoloader and configuration
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';

// Performance optimization includes
require_once __DIR__ . '/../../includes/performance/optimization.php';

use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$pageTitle = '404 - Không tìm thấy trang';
$pageDescription = 'Trang bạn đang tìm kiếm không tồn tại. Khám phá các phòng trọ, căn hộ cho thuê tại ' . getWebsiteName();

include __DIR__ . '/../../includes/layouts/client/header.php';
?>

<!-- Additional CSS for Error Pages -->
<link href="/assets/css/components/error.css" rel="stylesheet">
<div class="container-fluid p-0">
    <div class="error-hero" style="min-height: 80vh;">
        <div class="error-container">
            <!-- Error Icon -->
            <div class="error-icon error-404">
                <div class="error-icon-container">
                    <i class="fas fa-search"></i>
                </div>
            </div>

            <!-- Error Number -->
            <h1 class="error-number">404</h1>

            <!-- Error Content -->
            <div class="error-content">
                <h2 class="error-title">Không tìm thấy trang</h2>
                <p class="error-description">
                    Xin lỗi, trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.
                    Hãy thử tìm kiếm nội dung khác hoặc quay về trang chủ.
                </p>

                <!-- Error Actions -->
                <div class="error-actions">
                    <a href="/" class="btn-error btn-error-primary">
                        <i class="fas fa-home"></i>
                        Về trang chủ
                    </a>
                    <a href="/search" class="btn-error">
                        <i class="fas fa-search"></i>
                        Tìm kiếm phòng trọ
                    </a>
                </div>
            </div>

            <!-- Error Suggestions -->
            <div class="error-suggestions">
                <a href="/search?category=1" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3 class="error-suggestion-title">Phòng trọ</h3>
                    <p class="error-suggestion-desc">Tìm phòng trọ giá rẻ, tiện nghi</p>
                </a>

                <a href="/search?category=2" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3 class="error-suggestion-title">Căn hộ mini</h3>
                    <p class="error-suggestion-desc">Căn hộ mini, studio hiện đại</p>
                </a>

                <a href="/search?category=3" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-house"></i>
                    </div>
                    <h3 class="error-suggestion-title">Nhà nguyên căn</h3>
                    <p class="error-suggestion-desc">Nhà nguyên căn cho gia đình</p>
                </a>

                <a href="/contact" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3 class="error-suggestion-title">Hỗ trợ</h3>
                    <p class="error-suggestion-desc">Liên hệ để được hỗ trợ</p>
                </a>

                <a href="/about" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3 class="error-suggestion-title">Về chúng tôi</h3>
                    <p class="error-suggestion-desc">Tìm hiểu về Tro365</p>
                </a>

                <a href="/register" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="error-suggestion-title">Đăng ký</h3>
                    <p class="error-suggestion-desc">Tạo tài khoản miễn phí</p>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Add loading states for buttons
document.querySelectorAll('.btn-error').forEach(button => {
    button.addEventListener('click', function() {
        if (!this.classList.contains('btn-error-primary')) {
            this.style.opacity = '0.7';
            this.style.pointerEvents = 'none';

            setTimeout(() => {
                this.style.opacity = '';
                this.style.pointerEvents = '';
            }, 1000);
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>
