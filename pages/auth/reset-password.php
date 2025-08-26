<?php
/**
 * Reset Password Page
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
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /auth/forgot-password');
    exit;
}

// Verify token
$user = new User();
$userData = $user->getByResetToken($token);

if (!$userData) {
    $error = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    try {
        // Enhanced validation using rakit/validation
        $formData = [
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['confirm_password'] ?? ''
        ];

        $validation = \Tro365\Helpers\ValidationHelper::enhancedValidate($formData, [
            'password' => 'required|min:6|max:100',
            'password_confirmation' => 'required|same:password'
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'password.max' => 'Mật khẩu không được vượt quá 100 ký tự',
            'password_confirmation.required' => 'Vui lòng xác nhận mật khẩu',
            'password_confirmation.same' => 'Mật khẩu xác nhận không khớp'
        ]);

        if (!$validation['valid']) {
            $errors = [];
            foreach ($validation['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            throw new Exception(implode(', ', $errors));
        }

        $password = $formData['password'];
        
        // Reset password using token
        $user->resetPassword($token, $password);
        
        $success = 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập với mật khẩu mới.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Đặt lại mật khẩu';
$pageDescription = 'Đặt lại mật khẩu cho tài khoản của bạn';
$additionalCSS = [
    '/assets/css/client/auth.css',
    '/assets/css/client/glass-morphism.css'
];
$additionalJS = [
    '/assets/js/client/auth.js',
    '/assets/js/common.js'
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
                    <i class="fas fa-lock-open auth-icon"></i>
                    <h2>Đặt lại mật khẩu</h2>
                    <p class="auth-mb-0" style="opacity: 0.9;">Tạo mật khẩu mới cho tài khoản của bạn</p>
                </div>

                <div class="auth-body-enhanced">
                    <?php if ($error): ?>
                        <div class="auth-status-enhanced error auth-fade-in">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p class="auth-mb-0"><?= htmlspecialchars($error) ?></p>

                            <div class="auth-text-center auth-mt-4">
                                <a href="/auth/forgot-password" class="btn-enhanced btn-enhanced-primary">
                                    <i class="fas fa-redo"></i>
                                    Yêu cầu lại
                                </a>
                            </div>
                        </div>

                    <?php elseif ($success): ?>
                        <!-- Success State -->
                        <div class="auth-status-enhanced success auth-fade-in">
                            <i class="fas fa-check-circle status-icon"></i>
                            <h5>Mật khẩu đã được đặt lại!</h5>
                            <p><?= htmlspecialchars($success) ?></p>

                            <div class="auth-status-enhanced info" style="padding: 1.5rem; margin-top: 1.5rem;">
                                <i class="fas fa-info-circle" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                                <small>Bạn có thể đăng nhập ngay bây giờ với mật khẩu mới.</small>
                            </div>
                        </div>

                        <div class="auth-text-center auth-mt-4">
                            <a href="/auth/login" class="btn-enhanced btn-enhanced-primary">
                                <i class="fas fa-sign-in-alt"></i>
                                Đăng nhập ngay
                            </a>
                        </div>
                    <?php else: ?>

                        <!-- Info Section -->
                        <div class="auth-text-center auth-mb-4">
                            <div class="glass-icon-lg auth-mb-3" style="margin: 0 auto;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <p class="auth-text-secondary">Nhập mật khẩu mới cho tài khoản của bạn</p>
                        </div>

                        <!-- Reset Password Form -->
                        <form method="POST" class="auth-form-enhanced" id="resetPasswordForm">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                            <!-- New Password Field -->
                            <div class="auth-form-group-enhanced">
                                <label for="password" class="auth-form-label-enhanced">
                                    <i class="fas fa-key"></i>
                                    Mật khẩu mới
                                </label>
                                <div class="auth-input-group-enhanced">
                                    <input type="password"
                                           class="form-glass"
                                           id="password"
                                           name="password"
                                           placeholder="Nhập mật khẩu mới"
                                           required
                                           autocomplete="new-password">
                                    <button type="button" class="auth-input-toggle" id="toggleNewPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>

                                <!-- Password Strength Indicator -->
                                <div class="password-strength-container auth-mt-2">
                                    <div class="password-strength" id="passwordStrength"></div>
                                    <div class="password-strength-text" id="passwordStrengthText"></div>
                                </div>

                                <div class="auth-form-text">
                                    Mật khẩu phải có ít nhất <?= MIN_PASSWORD_LENGTH ?> ký tự
                                </div>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="auth-form-group-enhanced">
                                <label for="confirm_password" class="auth-form-label-enhanced">
                                    <i class="fas fa-check-double"></i>
                                    Xác nhận mật khẩu
                                </label>
                                <div class="auth-input-group-enhanced">
                                    <input type="password"
                                           class="form-glass"
                                           id="confirm_password"
                                           name="confirm_password"
                                           placeholder="Nhập lại mật khẩu mới"
                                           required
                                           autocomplete="new-password">
                                    <button type="button" class="auth-input-toggle" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="auth-form-feedback" id="confirmPasswordFeedback"></div>
                            </div>

                            <div class="auth-d-grid">
                                <button type="submit" class="btn-enhanced btn-enhanced-primary" id="resetPasswordBtn">
                                    <i class="fas fa-lock-open"></i>
                                    Đặt lại mật khẩu
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
                        <h6 class="auth-mb-0" style="font-weight: 600;">Bảo mật mật khẩu</h6>
                    </div>

                    <div class="auth-grid-gap-2" style="display: grid;">
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Ít nhất <?= MIN_PASSWORD_LENGTH ?> ký tự</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Kết hợp chữ hoa và chữ thường</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Bao gồm số và ký tự đặc biệt</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-check auth-text-success"></i>
                            <small class="auth-text-secondary">Không sử dụng thông tin cá nhân</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Security Tips -->
            <div class="auth-card-enhanced auth-mt-3">
                <div class="auth-body-enhanced" style="padding: 2rem;">
                    <div class="auth-d-flex auth-align-center auth-grid-gap-3 auth-mb-3">
                        <div class="glass-icon-sm">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h6 class="auth-mb-0" style="font-weight: 600;">Mẹo bảo mật</h6>
                    </div>

                    <div class="auth-grid-gap-2" style="display: grid;">
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-info auth-text-info"></i>
                            <small class="auth-text-secondary">Sử dụng mật khẩu duy nhất</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-info auth-text-info"></i>
                            <small class="auth-text-secondary">Kích hoạt xác thực 2 bước</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-info auth-text-info"></i>
                            <small class="auth-text-secondary">Cập nhật mật khẩu định kỳ</small>
                        </div>
                        <div class="auth-d-flex auth-align-center auth-grid-gap-2">
                            <i class="fas fa-info auth-text-info"></i>
                            <small class="auth-text-secondary">Không chia sẻ với người khác</small>
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
    // Initialize Tro365Auth if available
    if (window.Tro365Auth) {
        Tro365Auth.initThemeSupport();
        Tro365Auth.initMobileOptimizations();
        Tro365Auth.initLoadingStates();
        Tro365Auth.initPasswordStrength();
    }

    // Enhanced form validation
    const form = document.getElementById('resetPasswordForm');
    const newPasswordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const confirmPasswordFeedback = document.getElementById('confirmPasswordFeedback');
    const submitBtn = document.getElementById('resetPasswordBtn');

    // Password toggle functionality
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');

    if (toggleNewPassword) {
        toggleNewPassword.addEventListener('click', function() {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    if (toggleConfirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            const input = document.getElementById('confirm_password');
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }

    // Real-time password confirmation validation
    if (confirmPasswordInput && confirmPasswordFeedback) {
        confirmPasswordInput.addEventListener('input', function() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = this.value;

            if (confirmPassword === '') {
                confirmPasswordFeedback.textContent = '';
                confirmPasswordFeedback.className = 'auth-form-feedback';
                this.classList.remove('is-valid', 'is-invalid');
                return;
            }

            if (newPassword === confirmPassword) {
                confirmPasswordFeedback.textContent = 'Mật khẩu khớp';
                confirmPasswordFeedback.className = 'auth-form-feedback valid';
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                confirmPasswordFeedback.textContent = 'Mật khẩu không khớp';
                confirmPasswordFeedback.className = 'auth-form-feedback invalid';
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    }

    // Enhanced form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                confirmPasswordFeedback.textContent = 'Mật khẩu không khớp';
                confirmPasswordFeedback.className = 'auth-form-feedback invalid';
                confirmPasswordInput.classList.add('is-invalid');
                confirmPasswordInput.focus();
                return false;
            }

            if (newPassword.length < <?= MIN_PASSWORD_LENGTH ?>) {
                e.preventDefault();
                newPasswordInput.focus();
                return false;
            }

            // Add loading state
            if (submitBtn) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    }

    // Auto-focus first input
    if (newPasswordInput) {
        newPasswordInput.focus();
    }
});
</script>

<?php include_once __DIR__ . '/../../includes/layouts/client/footer.php'; ?>
