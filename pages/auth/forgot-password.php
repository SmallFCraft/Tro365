<?php
/**
 * Forgot Password Page
 * Tro365 - Website thuê trọ
 */

// Performance optimization includes
require_once __DIR__ . '/../../includes/performance/optimization.php';

use Tro365\Helpers\ValidationHelper;
use Tro365\Models\User;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = ValidationHelper::sanitize($_POST['email'] ?? '');
        
        if (empty($email)) {
            throw new Exception('Vui lòng nhập email');
        }
        
        // Enhanced validation using rakit/validation
        $validation = \Tro365\Helpers\ValidationHelper::enhancedValidate(['email' => $email], [
            'email' => 'required|email'
        ], [
            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không hợp lệ'
        ]);

        if (!$validation['valid']) {
            $errors = [];
            foreach ($validation['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            throw new Exception(implode(', ', $errors));
        }
        
        // Generate reset token
        $user = new User();
        $result = $user->generateResetToken($email);

        // Always show success message for security (prevent email enumeration)
        $success = 'Nếu email này đã được đăng ký, liên kết đặt lại mật khẩu sẽ được gửi đến hộp thư của bạn. Vui lòng kiểm tra email và làm theo hướng dẫn.';

        // Only send email if account actually exists
        if ($result['email_exists'] && $result['token']) {
            $resetLink = app_url("/auth/reset-password?token=" . $result['token']);
            $emailSent = sendPasswordResetEmail($email, $resetLink, $result['token']);

            if ($emailSent) {
                // Log successful password reset request
                \Tro365\Helpers\LoggerHelper::logAuth('password_reset_email_sent', ['email' => $email]);
            } else {
                \Tro365\Helpers\LoggerHelper::error('Password reset email failed', ['email' => $email]);
                // Don't throw exception - still show success message for security
            }
        } else {
            // Log attempt for non-existent email (for security monitoring)
            \Tro365\Helpers\LoggerHelper::logAuth('password_reset_attempt_invalid_email', ['email' => $email]);
        }

        // Handle AJAX requests (from ModernApp)
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $success,
                'redirect' => '/forgot-password?sent=1'
            ]);
            exit;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();

        // Handle AJAX error response
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $error
            ]);
            exit;
        }
    }
}

// Handle success redirect parameter
if (isset($_GET['sent']) && $_GET['sent'] == '1') {
    $success = 'Nếu email này đã được đăng ký, liên kết đặt lại mật khẩu sẽ được gửi đến hộp thư của bạn. Vui lòng kiểm tra email và làm theo hướng dẫn.';
}

// Additional CSS for auth pages
$additionalCSS = [
    '/assets/css/client/glass-morphism.css',
    '/assets/css/client/auth.css'
];

// Additional JS for auth pages - with defer to prevent race conditions
$additionalJS = [
    '/assets/js/client/auth.js'
];

// Add defer attribute to prevent race conditions with FormValidator
$jsDefer = true;

// Body class for auth pages
$bodyClass = 'auth-page';

include_once __DIR__ . '/../../includes/layouts/client/header.php';
?>

<div class="auth-page-enhanced">
    <div class="auth-layout-container">
        <!-- Main Content -->
        <div class="auth-main-content">
            <div class="auth-card-enhanced">
                <div class="auth-header-enhanced">
                    <i class="fas fa-key auth-icon"></i>
                    <h2>Quên mật khẩu</h2>
                    <p class="auth-mb-0" style="opacity: 0.9;">Đặt lại mật khẩu cho tài khoản của bạn</p>
                </div>

                <div class="auth-body-enhanced">
                    <?php if ($error): ?>
                        <div class="auth-status-enhanced error auth-fade-in">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p class="auth-mb-0"><?= htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <!-- Success State -->
                        <div class="auth-status-enhanced success auth-fade-in">
                            <i class="fas fa-check-circle status-icon"></i>
                            <h5>Email đã được gửi!</h5>
                            <p><?= htmlspecialchars($success) ?></p>

                            <div class="auth-status-enhanced info" style="padding: 1.5rem; margin-top: 1.5rem;">
                                <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                                <small>Kiểm tra cả thư mục spam nếu không thấy email trong hộp thư chính.</small>
                            </div>
                        </div>

                        <div class="auth-text-center auth-mt-4">
                            <a href="/auth/login" class="btn-enhanced btn-enhanced-primary">
                                <i class="fas fa-sign-in-alt"></i>
                                Đăng nhập
                            </a>
                        </div>

                    <?php else: ?>

                        <!-- Info Section -->
                        <div class="auth-text-center auth-mb-4">
                            <div class="glass-icon-lg auth-mb-3" style="margin: 0 auto;">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <p class="auth-text-secondary">
                                Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu.<br>
                                Chúng tôi sẽ gửi hướng dẫn chi tiết đến email của bạn.
                            </p>
                        </div>

                        <!-- Enhanced Form -->
                        <form method="POST" class="auth-form-enhanced" novalidate>
                            <div class="auth-form-group-enhanced">
                                <label for="email" class="form-label-enhanced">
                                    <i class="fas fa-envelope"></i>
                                    Email đã đăng ký
                                </label>
                                <div class="input-icon-enhanced">
                                    <input type="text"
                                           class="form-control form-control-enhanced"
                                           id="email"
                                           name="email"
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                           placeholder="Nhập email của bạn"
                                           data-required="true"
                                           data-type="email">
                                    <i class="fas fa-envelope input-icon"></i>
                                </div>
                                <div id="emailFeedback" class="invalid-feedback auth-text-danger">
                                    Vui lòng nhập email hợp lệ.
                                </div>
                                <small class="auth-text-muted" style="margin-top: 0.5rem; display: block;">
                                    Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu
                                </small>
                            </div>

                            <div class="auth-d-grid">
                                <button type="submit" class="btn-enhanced btn-enhanced-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    Gửi liên kết đặt lại
                                </button>
                            </div>
                        </form>

                        <!-- Divider -->
                        <div style="margin: 2rem 0; text-align: center; position: relative;">
                            <div style="height: 1px; background: var(--glass-border); margin: 1rem 0;"></div>
                            <span style="background: var(--bg-primary); padding: 0 1rem; color: var(--text-muted); font-size: 0.9rem;">hoặc</span>
                        </div>

                        <!-- Back to Login -->
                        <div class="auth-text-center">
                            <a href="/auth/login" class="btn-enhanced btn-enhanced-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Quay lại đăng nhập
                            </a>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="auth-sidebar">
            <!-- Security Info Section -->
            <div class="auth-card-enhanced">
                <div class="auth-body-enhanced" style="padding: 2rem;">
                    <div class="auth-d-flex auth-align-center auth-grid-gap-3 auth-mb-3">
                        <div class="glass-icon-sm">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h6 class="auth-mb-0" style="font-weight: 600;">Bảo mật thông tin</h6>
                    </div>

                    <div class="auth-grid-gap-2" style="display: grid;">
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Liên kết đặt lại có hiệu lực trong 1 giờ</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Chỉ gửi đến email đã đăng ký</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Liên kết chỉ sử dụng được một lần</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Thông tin được mã hóa an toàn</small>
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
    // Initialize Tro365 Auth validation for forgot password
    if (window.Tro365Auth && typeof window.Tro365Auth.initForgotPasswordEmailValidation === 'function') {
        // Use forgot password specific email validation (opposite logic of registration)
        window.Tro365Auth.initForgotPasswordEmailValidation();
        console.log('📧 Forgot password: Using forgot password email validation');
    }

    // Enhanced form focus handling only (submission handled by auth.js)
    const form = document.querySelector('.auth-form-enhanced');
    const emailInput = form?.querySelector('input[name="email"]');

    if (form && emailInput) {
        // Focus enhancement only
        emailInput.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
        });

        emailInput.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });

        // Let auth.js handle form submission to prevent duplicate handlers
        console.log('📧 Forgot password: Using standardized form submission from auth.js');
    }

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

    // Add shake animation keyframes
    if (!document.querySelector('#shake-animation')) {
        const shakeStyle = document.createElement('style');
        shakeStyle.id = 'shake-animation';
        shakeStyle.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(shakeStyle);
    }

    // Auto-focus email input
    if (emailInput && !emailInput.value) {
        setTimeout(() => {
            emailInput.focus();
        }, 500);
    }
});
</script>

<?php include_once __DIR__ . '/../../includes/layouts/client/footer.php'; ?>
