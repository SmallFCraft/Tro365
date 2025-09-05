<?php
/**
 * 500 Internal Server Error Page - Glass Morphism Design
 * Tro365 - Website thuê trọ
 */

// Set error response code
http_response_code(500);

// Include autoloader and configuration
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';

// Performance optimization includes
require_once __DIR__ . '/../../includes/performance/optimization.php';

use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$pageTitle = '500 - Lỗi máy chủ nội bộ';
$pageDescription = 'Đã xảy ra lỗi không mong muốn trên máy chủ. Chúng tôi đang khắc phục sự cố này.';

// Include header
include_once __DIR__ . '/../../includes/layouts/client/header.php';
?>

<!-- Additional CSS for Error Pages -->
<link href="/assets/css/components/error.css" rel="stylesheet">

<div class="container-fluid p-0">
    <div class="error-hero" style="min-height: 80vh;">
        <div class="error-container">
            <!-- Error Icon -->
            <div class="error-icon error-500">
                <div class="error-icon-container">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>

            <!-- Error Number -->
            <h1 class="error-number error-500">500</h1>

            <!-- Error Content -->
            <div class="error-content error-500">
                <h2 class="error-title">Lỗi máy chủ nội bộ</h2>
                <p class="error-description">
                    Xin lỗi, đã xảy ra lỗi không mong muốn trên máy chủ.
                    Chúng tôi đang khắc phục sự cố này và sẽ sớm khôi phục dịch vụ.
                </p>

                <!-- Error Actions -->
                <div class="error-actions">
                    <a href="/" class="btn-error btn-error-primary">
                        <i class="fas fa-home"></i>
                        Về trang chủ
                    </a>
                    <button onclick="location.reload()" class="btn-error" id="retryBtn">
                        <i class="fas fa-redo"></i>
                        Thử lại
                    </button>
                    <button onclick="history.back()" class="btn-error">
                        <i class="fas fa-arrow-left"></i>
                        Quay lại
                    </button>
                </div>
            </div>

            <!-- Error Suggestions -->
            <div class="error-suggestions">
                <a href="/" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3 class="error-suggestion-title">Trang chủ</h3>
                    <p class="error-suggestion-desc">Quay về trang chủ an toàn</p>
                </a>

                <a href="/search" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="error-suggestion-title">Tìm phòng trọ</h3>
                    <p class="error-suggestion-desc">Tìm kiếm phòng trọ khác</p>
                </a>

                <a href="/contact" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3 class="error-suggestion-title">Hỗ trợ</h3>
                    <p class="error-suggestion-desc">Liên hệ để được hỗ trợ</p>
                </a>

                <div class="error-suggestion-item" onclick="checkServerStatus()">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <h3 class="error-suggestion-title">Kiểm tra trạng thái</h3>
                    <p class="error-suggestion-desc">Xem tình trạng máy chủ</p>
                </div>

                <a href="/about" class="error-suggestion-item">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3 class="error-suggestion-title">Về chúng tôi</h3>
                    <p class="error-suggestion-desc">Thông tin về Tro365</p>
                </a>

                <div class="error-suggestion-item" onclick="reportError()">
                    <div class="error-suggestion-icon">
                        <i class="fas fa-bug"></i>
                    </div>
                    <h3 class="error-suggestion-title">Báo lỗi</h3>
                    <p class="error-suggestion-desc">Báo cáo sự cố này</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Information Panel -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="glass-card">
                <div class="text-center mb-4">
                    <div class="glass-icon-lg mx-auto mb-3">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Cần hỗ trợ ngay lập tức?</h3>
                    <p class="text-muted">
                        Nếu vấn đề vẫn tiếp tục, vui lòng liên hệ với chúng tôi qua các kênh sau:
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="glass-card-sm">
                            <h5><i class="fas fa-envelope text-primary me-2"></i>Email hỗ trợ</h5>
                            <p class="mb-2">
                                <strong><?= getCompanyInfo('email_admin', 'support@tro365.com') ?></strong>
                            </p>
                            <small class="text-muted">Phản hồi trong 24h</small>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-card-sm">
                            <h5><i class="fas fa-phone text-success me-2"></i>Hotline</h5>
                            <p class="mb-2">
                                <strong><?= getCompanyInfo('sdt_hotline', '1900-365-365') ?></strong>
                            </p>
                            <small class="text-muted">24/7 hỗ trợ</small>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-card-sm">
                            <h5><i class="fab fa-facebook text-primary me-2"></i>Facebook</h5>
                            <p class="mb-2">
                                <strong><?= getCompanyInfo('facebook', 'fb.com/tro365') ?></strong>
                            </p>
                            <small class="text-muted">Tin nhắn trực tiếp</small>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-card-sm">
                            <h5><i class="fab fa-telegram text-info me-2"></i>Telegram</h5>
                            <p class="mb-2">
                                <strong><?= getCompanyInfo('telegram', '@tro365support') ?></strong>
                            </p>
                            <small class="text-muted">Hỗ trợ nhanh</small>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="/contact" class="btn-glass btn-glass-primary">
                        <i class="fas fa-paper-plane me-2"></i>
                        Gửi yêu cầu hỗ trợ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Removed unused Server Status Modal -->

<script>
// Retry button with loading state
document.getElementById('retryBtn').addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thử lại...';
    btn.disabled = true;

    setTimeout(() => {
        location.reload();
    }, 1500);
});

// Removed dead functions: checkServerStatus, reportError, autoRetry
// These were placeholder functions never called in production
</script>

<?php
// Include footer
include_once __DIR__ . '/../../includes/layouts/client/footer.php';
?>
