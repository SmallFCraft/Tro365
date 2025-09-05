<?php
/**
 * Resend Email Verification Page
 * Tro365 - Website thuê trọ
 */

// Performance optimization includes
require_once __DIR__ . '/../../includes/performance/optimization.php';

use Tro365\Models\User;
use Tro365\Core\Auth;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

// Check if user is already logged in and verified
$auth = new Auth();
if ($auth->isLoggedIn()) {
    $currentUser = $auth->getCurrentUser();
    // If user is logged in and already verified, redirect to profile
    if (!empty($currentUser['email_verified_at'])) {
        setFlashMessage(MSG_SUCCESS, 'Email của bạn đã được xác thực.');
        redirect('/profile');
    }
    // If user is logged in but not verified, continue to allow resend
}

$user = new User();

$error = '';
$success = '';

// Handle GET request for logged in users (from profile page)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $auth->isLoggedIn()) {
    try {
        $currentUser = $auth->getCurrentUser();

        // Check if email verification is required
        if (!isEmailVerificationRequired()) {
            throw new Exception('Tính năng xác thực email hiện không được bật');
        }

        // Check if already verified
        if (!empty($currentUser['email_verified_at'])) {
            setFlashMessage(MSG_SUCCESS, 'Email của bạn đã được xác thực.');
            redirect('/profile');
        }

        // Resend verification email for current user
        $result = $user->resendEmailVerification($currentUser['Email']);

        if ($result) {
            // Send verification email
            $verificationLink = app_url("/verify-email?token=" . $result['token']);
            $emailSent = sendEmailVerification(
                $result['user']['Email'],
                $result['user']['HoTen'],
                $verificationLink,
                $result['token']
            );

            if ($emailSent) {
                setFlashMessage(MSG_SUCCESS, 'Email xác thực đã được gửi lại. Vui lòng kiểm tra hộp thư của bạn.');
                writeLog("Email verification resent to: " . $currentUser['Email']);
                redirect('/profile');
            } else {
                throw new Exception('Có lỗi xảy ra khi gửi email. Vui lòng thử lại sau.');
            }
        }

    } catch (Exception $e) {
        setFlashMessage(MSG_ERROR, $e->getMessage());
        writeLog("Resend verification failed: " . $e->getMessage());
        redirect('/profile');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }
        
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            throw new Exception('Vui lòng nhập email');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email không hợp lệ');
        }
        
        // Check if email verification is required
        if (!isEmailVerificationRequired()) {
            throw new Exception('Tính năng xác thực email hiện không được bật');
        }
        
        // Resend verification email
        $result = $user->resendEmailVerification($email);
        
        if ($result) {
            // Send verification email
            $verificationLink = app_url("/verify-email?token=" . $result['token']);
            $emailSent = sendEmailVerification(
                $result['user']['Email'], 
                $result['user']['HoTen'], 
                $verificationLink, 
                $result['token']
            );
            
            if ($emailSent) {
                $success = 'Email xác thực đã được gửi lại. Vui lòng kiểm tra hộp thư của bạn.';
                writeLog("Email verification resent to: " . $email);
            } else {
                throw new Exception('Có lỗi xảy ra khi gửi email. Vui lòng thử lại sau.');
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        writeLog("Resend verification failed: " . $e->getMessage());
    }
}

$pageTitle = 'Gửi lại email xác thực';
$pageDescription = 'Gửi lại email xác thực để hoàn tất đăng ký tài khoản';

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
                    <i class="fas fa-paper-plane auth-icon"></i>
                    <h2>Gửi lại email xác thực</h2>
                    <p class="auth-mb-0" style="opacity: 0.9;">Nhận lại email xác thực để hoàn tất đăng ký</p>
                </div>

                <div class="auth-body-enhanced">
                    <?php if ($success): ?>
                        <!-- Success State -->
                        <div class="auth-status-enhanced success auth-fade-in">
                            <i class="fas fa-check-circle status-icon"></i>
                            <h5>Đã gửi thành công!</h5>
                            <p><?= e($success) ?></p>

                            <div class="auth-status-enhanced info" style="padding: 1.5rem; margin-top: 1.5rem;">
                                <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                                <small>Kiểm tra cả thư mục spam nếu không thấy email trong hộp thư chính.</small>
                            </div>
                        </div>

                        <div class="auth-text-center auth-mt-4">
                            <a href="<?= app_url('/login') ?>" class="btn-enhanced btn-enhanced-primary">
                                <i class="fas fa-sign-in-alt"></i>
                                Đăng nhập
                            </a>
                        </div>

                    <?php else: ?>

                        <?php if ($error): ?>
                            <div class="auth-status-enhanced error auth-fade-in">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p class="auth-mb-0"><?= e($error) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Info Section -->
                        <div class="auth-text-center auth-mb-4">
                            <div class="glass-icon-lg auth-mb-3" style="margin: 0 auto;">
                                <i class="fas fa-envelope-open-text"></i>
                            </div>
                            <p class="auth-text-secondary">
                                Nhập email của bạn để nhận lại email xác thực.<br>
                                Email xác thực sẽ được gửi đến địa chỉ email đã đăng ký.
                            </p>
                        </div>

                        <!-- Enhanced Form -->
                        <form method="POST" class="auth-form-enhanced needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                            <div class="form-group-enhanced">
                                <label for="email" class="form-label-enhanced">
                                    <i class="fas fa-envelope"></i>
                                    Email đã đăng ký
                                </label>
                                <div class="input-icon-enhanced">
                                    <input type="email"
                                           class="form-control-enhanced"
                                           id="email"
                                           name="email"
                                           value="<?= e($_POST['email'] ?? '') ?>"
                                           placeholder="Nhập email của bạn"
                                           required>
                                    <i class="fas fa-envelope input-icon"></i>
                                </div>
                                <div class="invalid-feedback auth-text-danger">
                                    Vui lòng nhập email hợp lệ.
                                </div>
                            </div>

                            <div class="auth-d-grid">
                                <button type="submit" class="btn-enhanced btn-enhanced-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    Gửi lại email xác thực
                                </button>
                            </div>
                        </form>

                        <!-- Divider -->
                        <div style="margin: 2rem 0; text-align: center; position: relative;">
                            <div style="height: 1px; background: var(--glass-border); margin: 1rem 0;"></div>
                            <span style="background: var(--bg-primary); padding: 0 1rem; color: var(--text-muted); font-size: 0.9rem;">hoặc</span>
                        </div>

                        <!-- Alternative Action -->
                        <div class="auth-text-center">
                            <p class="auth-text-muted auth-mb-3">Đã xác thực email?</p>
                            <a href="<?= app_url('/login') ?>" class="btn-enhanced btn-enhanced-secondary">
                                <i class="fas fa-sign-in-alt"></i>
                                Đăng nhập ngay
                            </a>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="auth-sidebar">
            <!-- Info Section -->
            <div class="auth-card-enhanced">
                <div class="auth-body-enhanced" style="padding: 2rem;">
                    <div class="auth-d-flex auth-align-center auth-grid-gap-3 auth-mb-3">
                        <div class="glass-icon-sm">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h6 class="auth-mb-0" style="font-weight: 600;">Lưu ý quan trọng</h6>
                    </div>

                    <div class="auth-grid-gap-2" style="display: grid;">
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Email xác thực có hiệu lực trong 24 giờ</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Kiểm tra cả thư mục spam/junk mail</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Chỉ có thể gửi lại sau 5 phút từ lần gửi trước</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Liên hệ hỗ trợ nếu vẫn không nhận được email</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript for better UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced form validation
    const forms = document.querySelectorAll('.needs-validation');

    forms.forEach(form => {
        const emailInput = form.querySelector('input[type="email"]');
        const submitButton = form.querySelector('button[type="submit"]');

        // Real-time email validation
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                const email = this.value.trim();
                const isValid = email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

                if (isValid) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else if (email) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
            });

            // Focus enhancement
            emailInput.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });

            emailInput.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        }

        // Form submission handling
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                // Shake animation for invalid form
                form.style.animation = 'shake 0.5s ease-in-out';
                setTimeout(() => {
                    form.style.animation = '';
                }, 500);
            } else {
                // Add loading state to submit button
                if (submitButton && !submitButton.classList.contains('loading')) {
                    submitButton.classList.add('loading');
                    submitButton.disabled = true;
                }
            }

            form.classList.add('was-validated');
        });
    });

    // Add loading animation to buttons
    document.querySelectorAll('.btn-enhanced').forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.type !== 'submit' && !this.classList.contains('loading')) {
                this.classList.add('loading');
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 2000);
            }
        });
    });

    // Countdown timer for resend functionality (if applicable)
    const resendButton = document.querySelector('button[type="submit"]');
    if (resendButton && localStorage.getItem('lastResendTime')) {
        const lastResendTime = parseInt(localStorage.getItem('lastResendTime'));
        const currentTime = Date.now();
        const timeDiff = currentTime - lastResendTime;
        const cooldownTime = 5 * 60 * 1000; // 5 minutes

        if (timeDiff < cooldownTime) {
            const remainingTime = Math.ceil((cooldownTime - timeDiff) / 1000);
            startCountdown(resendButton, remainingTime);
        }
    }

    // Countdown function
    function startCountdown(button, seconds) {
        button.disabled = true;
        button.classList.add('loading');

        const originalText = button.innerHTML;

        const interval = setInterval(() => {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            button.innerHTML = `<i class="fas fa-clock"></i> Chờ ${minutes}:${secs.toString().padStart(2, '0')}`;

            seconds--;

            if (seconds < 0) {
                clearInterval(interval);
                button.disabled = false;
                button.classList.remove('loading');
                button.innerHTML = originalText;
            }
        }, 1000);
    }

    // Store resend time on form submission
    document.querySelector('form')?.addEventListener('submit', function() {
        localStorage.setItem('lastResendTime', Date.now().toString());
    });

    // Add shake animation keyframes
    if (!document.querySelector('#shake-animation')) {
        const verifyStyle = document.createElement('style');
        verifyStyle.id = 'shake-animation';
        verifyStyle.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(verifyStyle);
    }
});
</script>

<?php include_once __DIR__ . '/../../includes/layouts/client/footer.php'; ?>
