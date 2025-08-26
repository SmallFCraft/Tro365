<?php
/**
 * Forgot Password Page
 * Tro365 - Website thuê trọ
 */

use Tro365\Helpers\ValidationHelper;
use Tro365\Models\User;

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

        // Send reset email using helper function
        $resetLink = app_url("/auth/reset-password?token=" . $result['token']);

        $emailSent = sendPasswordResetEmail($email, $resetLink, $result['token']);

        if ($emailSent) {
            $success = 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư và làm theo hướng dẫn.';

            // Log successful password reset request
            \Tro365\Helpers\LoggerHelper::logAuth('password_reset_email_sent', ['email' => $email]);
        } else {
            \Tro365\Helpers\LoggerHelper::error('Password reset email failed', ['email' => $email]);
            throw new Exception('Có lỗi xảy ra khi gửi email đặt lại mật khẩu. Vui lòng thử lại sau.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

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
                        <form method="POST" class="auth-form-enhanced needs-validation" novalidate>
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
                                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                           placeholder="Nhập email của bạn"
                                           required>
                                    <i class="fas fa-envelope input-icon"></i>
                                </div>
                                <div class="invalid-feedback auth-text-danger">
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
    // Enhanced form validation
    const form = document.querySelector('.auth-form-enhanced');
    const emailInput = form?.querySelector('input[type="email"]');
    const submitButton = form?.querySelector('button[type="submit"]');

    if (form && emailInput) {
        // Real-time email validation
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
        const style = document.createElement('style');
        style.id = 'shake-animation';
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
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
