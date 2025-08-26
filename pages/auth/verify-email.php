<?php
/**
 * Email Verification Page
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;
use Tro365\Models\User;

$auth = new Auth();
$user = new User();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Check if token is provided
if (empty($token)) {
    $error = 'Token xác thực không hợp lệ';
} else {
    try {
        // Verify email with token
        $verifiedUser = $user->verifyEmail($token);
        
        if ($verifiedUser) {
            $success = 'Email đã được xác thực thành công! Bạn có thể đăng nhập ngay bây giờ.';
            
            // Log successful verification
            writeLog("Email verified successfully for user: " . $verifiedUser['Email']);
            
            // Auto login the user after verification
            $userData = $user->getById($verifiedUser['ID']);
            $auth->loginUser($userData);
            
            // Redirect to home page after 3 seconds
            header("refresh:3;url=" . app_url());
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        writeLog("Email verification failed: " . $e->getMessage());
    }
}

$pageTitle = 'Xác thực email';
$pageDescription = 'Xác thực email để hoàn tất đăng ký tài khoản';

// Additional CSS for auth pages
$additionalCSS = [
    '/assets/css/client/glass-morphism.css',
    '/assets/css/client/auth.css'
];

// Additional JS for auth pages
$additionalJS = [
    '/assets/js/client/auth.js'
];

// Body class for auth pages
$bodyClass = 'auth-page';

// Include header
include_once __DIR__ . '/../../includes/layouts/client/header.php';
?>

<div class="auth-page-enhanced">
    <div class="auth-layout-container">
        <!-- Main Content -->
        <div class="auth-main-content">
            <div class="auth-card-enhanced">
                <div class="auth-header-enhanced">
                    <i class="fas fa-envelope-check auth-icon"></i>
                    <h2>Xác thực email</h2>
                    <p class="auth-mb-0" style="opacity: 0.9;">Hoàn tất quá trình đăng ký tài khoản</p>
                </div>

                <div class="auth-body-enhanced">
                    <?php if ($success): ?>
                        <!-- Success State -->
                        <div class="auth-status-enhanced success auth-fade-in">
                            <i class="fas fa-check-circle status-icon"></i>
                            <h5>Xác thực thành công!</h5>
                            <p><?= e($success) ?></p>

                            <!-- Progress Bar -->
                            <div class="auth-progress-enhanced">
                                <div class="progress-bar" style="width: 100%"></div>
                            </div>

                            <div class="auth-d-flex auth-align-center auth-justify-center auth-grid-gap-2 auth-mb-3">
                                <div class="loading-spinner" style="width: 20px; height: 20px; border-width: 2px;"></div>
                                <small class="auth-text-muted">Đang chuyển hướng về trang chủ...</small>
                            </div>
                        </div>

                        <div class="auth-text-center">
                            <a href="<?= app_url() ?>" class="btn-enhanced btn-enhanced-success">
                                <i class="fas fa-home"></i>
                                Về trang chủ
                            </a>
                        </div>

                    <?php elseif ($error): ?>
                        <!-- Error State -->
                        <div class="auth-status-enhanced error auth-fade-in">
                            <i class="fas fa-exclamation-triangle status-icon"></i>
                            <h5>Xác thực thất bại!</h5>
                            <p><?= e($error) ?></p>
                        </div>

                        <div class="auth-text-center auth-mt-4">
                            <div class="auth-status-enhanced info" style="padding: 1.5rem; margin-bottom: 2rem;">
                                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p class="auth-mb-0">
                                    Token có thể đã hết hạn hoặc đã được sử dụng.<br>
                                    Bạn có thể yêu cầu gửi lại email xác thực.
                                </p>
                            </div>

                            <div class="auth-d-grid auth-grid-gap-3">
                                <a href="<?= app_url('/resend-verification') ?>" class="btn-enhanced btn-enhanced-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    Gửi lại email xác thực
                                </a>
                                <a href="<?= app_url('/login') ?>" class="btn-enhanced btn-enhanced-secondary">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Đăng nhập
                                </a>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Loading State -->
                        <div class="auth-loading-enhanced auth-fade-in">
                            <div class="loading-spinner"></div>
                            <h5>Đang xử lý xác thực...</h5>
                            <p>Vui lòng đợi trong giây lát</p>

                            <!-- Progress Bar -->
                            <div class="auth-progress-enhanced">
                                <div class="progress-bar" style="width: 60%"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="auth-sidebar">
            <!-- Help Section -->
            <div class="auth-card-enhanced">
                <div class="auth-body-enhanced" style="padding: 2rem;">
                    <div class="auth-d-flex auth-align-center auth-grid-gap-3 auth-mb-3">
                        <div class="glass-icon-sm">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h6 class="auth-mb-0" style="font-weight: 600;">Cần hỗ trợ?</h6>
                    </div>

                    <p class="auth-text-secondary auth-mb-3" style="font-size: 0.9rem;">
                        Nếu bạn gặp vấn đề với việc xác thực email, vui lòng liên hệ với chúng tôi:
                    </p>

                    <div class="auth-d-grid auth-grid-gap-2">
                        <a href="mailto:<?= getCompanyInfo('email_lien_he', 'contact@tro.loading99.site') ?>"
                           class="btn-enhanced btn-enhanced-secondary">
                            <i class="fas fa-envelope"></i>
                            Email hỗ trợ
                        </a>
                        <a href="<?= app_url('/contact') ?>"
                           class="btn-enhanced btn-enhanced-secondary">
                            <i class="fas fa-phone"></i>
                            Liên hệ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript for better UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-redirect countdown for success state
    <?php if ($success): ?>
    let countdown = 3;
    const countdownElement = document.querySelector('.auth-text-muted');
    if (countdownElement) {
        const interval = setInterval(() => {
            countdown--;
            if (countdown > 0) {
                countdownElement.textContent = `Đang chuyển hướng về trang chủ trong ${countdown} giây...`;
            } else {
                countdownElement.textContent = 'Đang chuyển hướng...';
                clearInterval(interval);
            }
        }, 1000);
    }
    <?php endif; ?>

    // Add loading animation to buttons
    document.querySelectorAll('.btn-enhanced').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!this.classList.contains('loading')) {
                this.classList.add('loading');
                // Remove loading state after 3 seconds as fallback
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 3000);
            }
        });
    });

    // Add smooth scroll behavior
    document.documentElement.style.scrollBehavior = 'smooth';

    // Add focus management for accessibility
    const firstFocusableElement = document.querySelector('.btn-enhanced, a[href]');
    if (firstFocusableElement && window.location.hash === '') {
        setTimeout(() => {
            firstFocusableElement.focus();
        }, 100);
    }
});
</script>

<?php include_once __DIR__ . '/../../includes/layouts/client/footer.php'; ?>
