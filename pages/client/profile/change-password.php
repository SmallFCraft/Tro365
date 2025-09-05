<?php
/**
 * Change Password Page - Glass Morphism UI
 * Tro365 - Website thuê trọ
 * Mobile-First Responsive Design with Light/Dark Mode
 */

// Load configuration and dependencies
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/constants.php';

// Performance optimization includes
require_once __DIR__ . '/../../../includes/performance/optimization.php';

// Load helper functions
require_once __DIR__ . '/../../../includes/functions/helpers.php';
require_once __DIR__ . '/../../../includes/functions/auth.php';
require_once __DIR__ . '/../../../includes/functions/validation.php';

use Tro365\Core\Auth;
use Tro365\Models\User;
use Tro365\Activity;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();
$user = new User();

// Require login
if (!$auth->isLoggedIn()) {
    setFlashMessage(MSG_ERROR, 'Vui lòng đăng nhập để đổi mật khẩu');
    redirect('/login');
}

$currentUser = $auth->getCurrentUser();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate input
        if (empty($currentPassword)) {
            throw new Exception('Vui lòng nhập mật khẩu hiện tại');
        }

        if (empty($newPassword)) {
            throw new Exception('Vui lòng nhập mật khẩu mới');
        }

        if (empty($confirmPassword)) {
            throw new Exception('Vui lòng xác nhận mật khẩu mới');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('Mật khẩu mới và xác nhận mật khẩu không khớp');
        }

        if (strlen($newPassword) < 6) {
            throw new Exception('Mật khẩu mới phải có ít nhất 6 ký tự');
        }

        if ($currentPassword === $newPassword) {
            throw new Exception('Mật khẩu mới phải khác mật khẩu hiện tại');
        }

        // Change password
        $user->changePassword($currentUser['ID'], $currentPassword, $newPassword);

        // Log activity
        try {
            $activity = new Activity();
            $activity->logPasswordChange($currentUser['ID']);
        } catch (Exception $e) {
            // Silent fail for activity logging
            writeLog("Activity log error: " . $e->getMessage());
        }

        $success = 'Đổi mật khẩu thành công!';

        // Clear form data
        $_POST = [];

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Đổi mật khẩu';
$pageDescription = 'Đổi mật khẩu tài khoản với giao diện Glass Morphism hiện đại';

// Additional CSS files for this page
$additionalCSS = [
    '/assets/css/client/glass-morphism.css',
    '/assets/css/client/profile.css'
];

include_once __DIR__ . '/../../../includes/layouts/client/header.php';
?>

<!-- Glass Morphism Password Change Hero -->
<section class="profile-hero">
    <div class="profile-hero-content">
        <div class="profile-container">
            <div class="profile-avatar-container">
                <div class="glass-icon-lg" style="background: rgba(255, 193, 7, 0.2); border-color: rgba(255, 193, 7, 0.3); color: #ffc107;">
                    <i class="fas fa-key"></i>
                </div>
            </div>

            <div class="profile-info">
                <h2>Đổi mật khẩu</h2>
                <div class="profile-info-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Cập nhật mật khẩu để bảo mật tài khoản của bạn</span>
                </div>

                <div class="profile-role-badge">
                    <i class="fas fa-user"></i>
                    <span><?= e($currentUser['HoTen']) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="profile-container">
    <!-- Glass Morphism Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <div class="glass-container" style="padding: 1rem 1.5rem; border-radius: 15px;">
            <ol class="breadcrumb mb-0" style="background: transparent;">
                <li class="breadcrumb-item">
                    <a href="/" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i>
                        Trang chủ
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/profile" class="text-decoration-none">
                        <i class="fas fa-user me-1"></i>
                        Hồ sơ cá nhân
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <i class="fas fa-key me-1"></i>
                    Đổi mật khẩu
                </li>
            </ol>
        </div>
    </nav>

    <!-- Glass Morphism Layout Grid -->
    <div class="glass-grid-2">
        <!-- Main Change Password Form -->
        <div class="glass-panel">
            <div class="settings-card-header">
                <div class="settings-card-icon" style="background: rgba(255, 193, 7, 0.15); border-color: rgba(255, 193, 7, 0.2); color: #ffc107;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="settings-card-title">
                    <h5>Thay đổi mật khẩu</h5>
                    <p>Cập nhật mật khẩu để bảo mật tài khoản</p>
                </div>
            </div>

            <!-- Alert Messages with Glass Morphism -->
            <?php if ($error): ?>
                <div class="glass-container mb-4" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); padding: 1rem 1.5rem;">
                    <div class="d-flex align-items-center">
                        <div class="glass-icon-sm me-3" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong>Lỗi!</strong> <?= e($error) ?>
                        </div>
                        <button type="button" class="btn-close" onclick="this.parentElement.parentElement.style.display='none'"></button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="glass-container mb-4" style="background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3); padding: 1rem 1.5rem;">
                    <div class="d-flex align-items-center">
                        <div class="glass-icon-sm me-3" style="background: rgba(34, 197, 94, 0.2); color: #22c55e;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong>Thành công!</strong> <?= e($success) ?>
                        </div>
                        <button type="button" class="btn-close" onclick="this.parentElement.parentElement.style.display='none'"></button>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" id="changePasswordForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <!-- Current Password Section -->
                <div class="settings-card mb-4">
                    <div class="settings-card-header">
                        <div class="settings-card-icon" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.2); color: #ef4444;">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="settings-card-title">
                            <h5>Mật khẩu hiện tại</h5>
                            <p>Nhập mật khẩu hiện tại để xác thực</p>
                        </div>
                    </div>

                    <div class="settings-card-content">
                        <div class="position-relative">
                            <input type="password"
                                   class="form-glass"
                                   id="current_password"
                                   name="current_password"
                                   placeholder="Nhập mật khẩu hiện tại"
                                   style="padding-right: 50px;"
                                   required>
                            <button type="button"
                                    class="position-absolute top-50 end-0 translate-middle-y me-3 btn p-0"
                                    style="background: none; border: none; color: var(--text-secondary);"
                                    onclick="togglePassword('current_password', 'toggleIcon1')">
                                <i class="fas fa-eye" id="toggleIcon1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- New Password Section -->
                <div class="settings-card mb-4">
                    <div class="settings-card-header">
                        <div class="settings-card-icon" style="background: rgba(34, 197, 94, 0.15); border-color: rgba(34, 197, 94, 0.2); color: #22c55e;">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="settings-card-title">
                            <h5>Mật khẩu mới</h5>
                            <p>Tạo mật khẩu mạnh để bảo vệ tài khoản</p>
                        </div>
                    </div>

                    <div class="settings-card-content">
                        <div class="row">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <label for="new_password" class="form-label fw-semibold">
                                    <i class="fas fa-key me-2 text-primary"></i>
                                    Mật khẩu mới <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="password"
                                           class="form-glass"
                                           id="new_password"
                                           name="new_password"
                                           placeholder="Nhập mật khẩu mới"
                                           style="padding-right: 50px;"
                                           minlength="6"
                                           required>
                                    <button type="button"
                                            class="position-absolute top-50 end-0 translate-middle-y me-3 btn p-0"
                                            style="background: none; border: none; color: var(--text-secondary);"
                                            onclick="togglePassword('new_password', 'toggleIcon2')">
                                        <i class="fas fa-eye" id="toggleIcon2"></i>
                                    </button>
                                </div>

                                <!-- Password Strength Indicator -->
                                <div class="password-strength mt-2" id="passwordStrength"></div>
                                <div class="password-strength-text" id="passwordStrengthText"></div>

                                <div class="settings-info-box mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Tối thiểu 6 ký tự, nên có chữ hoa, chữ thường, số và ký tự đặc biệt</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">
                                    <i class="fas fa-check-double me-2 text-primary"></i>
                                    Xác nhận mật khẩu mới <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="password"
                                           class="form-glass"
                                           id="confirm_password"
                                           name="confirm_password"
                                           placeholder="Nhập lại mật khẩu mới"
                                           style="padding-right: 50px;"
                                           minlength="6"
                                           required>
                                    <button type="button"
                                            class="position-absolute top-50 end-0 translate-middle-y me-3 btn p-0"
                                            style="background: none; border: none; color: var(--text-secondary);"
                                            onclick="togglePassword('confirm_password', 'toggleIcon3')">
                                        <i class="fas fa-eye" id="toggleIcon3"></i>
                                    </button>
                                </div>

                                <!-- Password Match Indicator -->
                                <div class="mt-2" id="passwordMatch"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Glass Morphism Action Buttons -->
                <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-center">
                    <a href="/profile" class="btn-glass order-2 order-md-1">
                        <i class="fas fa-arrow-left"></i>
                        <span>Quay lại</span>
                    </a>
                    <button type="submit" class="btn-glass" style="background: rgba(255, 193, 7, 0.2); border-color: rgba(255, 193, 7, 0.3); color: #ffc107;" id="submitBtn">
                        <i class="fas fa-save"></i>
                        <span>Đổi mật khẩu</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Glass Morphism Security Tips Sidebar -->
        <div class="glass-panel">
            <div class="settings-card-header">
                <div class="settings-card-icon" style="background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="settings-card-title">
                    <h5>Mẹo bảo mật</h5>
                    <p>Hướng dẫn tạo mật khẩu mạnh</p>
                </div>
            </div>

            <div class="settings-card-content">
                <!-- Security Tips Grid -->
                <div class="row">
                    <div class="col-12">
                        <div class="settings-item mb-3">
                            <div class="settings-item-info">
                                <div class="settings-item-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h6>Mật khẩu mạnh</h6>
                                    <small>Sử dụng ít nhất 8 ký tự</small>
                                </div>
                            </div>
                        </div>

                        <div class="settings-item mb-3">
                            <div class="settings-item-info">
                                <div class="settings-item-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h6>Kết hợp ký tự</h6>
                                    <small>Chữ hoa, chữ thường, số và ký tự đặc biệt</small>
                                </div>
                            </div>
                        </div>

                        <div class="settings-item mb-3">
                            <div class="settings-item-info">
                                <div class="settings-item-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h6>Tránh thông tin cá nhân</h6>
                                    <small>Không sử dụng tên, ngày sinh dễ đoán</small>
                                </div>
                            </div>
                        </div>

                        <div class="settings-item mb-3">
                            <div class="settings-item-info">
                                <div class="settings-item-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h6>Thay đổi định kỳ</h6>
                                    <small>Cập nhật mật khẩu thường xuyên</small>
                                </div>
                            </div>
                        </div>

                        <div class="settings-item mb-3">
                            <div class="settings-item-info">
                                <div class="settings-item-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h6>Bảo mật tuyệt đối</h6>
                                    <small>Không chia sẻ với người khác</small>
                                </div>
                            </div>
                        </div>

                        <div class="settings-item">
                            <div class="settings-item-info">
                                <div class="settings-item-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h6>Đăng xuất an toàn</h6>
                                    <small>Luôn đăng xuất khi dùng máy chung</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Security Notice -->
                <div class="glass-container mt-4" style="background: rgba(255, 193, 7, 0.1); border-color: rgba(255, 193, 7, 0.2); padding: 1.5rem;">
                    <div class="d-flex align-items-start">
                        <div class="glass-icon-sm me-3" style="background: rgba(255, 193, 7, 0.2); color: #ffc107;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h6 class="mb-2">Lưu ý quan trọng</h6>
                            <p class="mb-0 small">Sau khi đổi mật khẩu, bạn sẽ cần đăng nhập lại trên tất cả thiết bị khác.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/layouts/client/footer.php'; ?>

<!-- Enhanced JavaScript for Glass Morphism Change Password -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility with enhanced UX
        window.togglePassword = function(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordField && toggleIcon) {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    toggleIcon.className = 'fas fa-eye-slash';
                    toggleIcon.style.color = 'var(--primary-color)';
                } else {
                    passwordField.type = 'password';
                    toggleIcon.className = 'fas fa-eye';
                    toggleIcon.style.color = 'var(--text-secondary)';
                }
            }
        };

        // Enhanced password strength checker with glass morphism styling
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];

            if (password.length >= 8) {
                strength += 1;
            } else {
                feedback.push('Ít nhất 8 ký tự');
            }

            if (/[a-z]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('Chữ thường');
            }

            if (/[A-Z]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('Chữ hoa');
            }

            if (/[0-9]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('Số');
            }

            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('Ký tự đặc biệt');
            }

            return { strength, feedback };
        }

        // Enhanced password strength indicator with glass morphism
        function updatePasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordStrengthText');

            if (password.length === 0) {
                strengthBar.innerHTML = '';
                strengthText.innerHTML = '';
                return;
            }

            const result = checkPasswordStrength(password);
            const percentage = (result.strength / 5) * 100;

            let color = '#ef4444';
            let bgColor = 'rgba(239, 68, 68, 0.1)';
            let text = 'Yếu';

            if (result.strength >= 4) {
                color = '#22c55e';
                bgColor = 'rgba(34, 197, 94, 0.1)';
                text = 'Mạnh';
            } else if (result.strength >= 3) {
                color = '#ffc107';
                bgColor = 'rgba(255, 193, 7, 0.1)';
                text = 'Trung bình';
            }

            strengthBar.innerHTML = `
                <div class="glass-container mt-2" style="padding: 0.5rem; background: ${bgColor}; border-color: ${color}40;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1" style="height: 4px; background: rgba(255, 255, 255, 0.2); border-radius: 2px; overflow: hidden;">
                            <div style="width: ${percentage}%; height: 100%; background: ${color}; transition: width 0.3s ease;"></div>
                        </div>
                        <small style="color: ${color}; font-weight: 600;">${percentage.toFixed(0)}%</small>
                    </div>
                </div>
            `;

            strengthText.innerHTML = `
                <div class="settings-info-box mt-2" style="background: ${bgColor}; border-color: ${color}40;">
                    <i class="fas fa-shield-alt" style="color: ${color};"></i>
                    <span>
                        <strong>Độ mạnh: ${text}</strong>
                        ${result.feedback.length > 0 ? ' - Cần: ' + result.feedback.join(', ') : ' - Mật khẩu đạt yêu cầu!'}
                    </span>
                </div>
            `;
        }

        // Enhanced password match checker with glass morphism
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('passwordMatch');

            if (confirmPassword.length === 0) {
                matchText.innerHTML = '';
                return;
            }

            if (newPassword === confirmPassword) {
                matchText.innerHTML = `
                    <div class="settings-info-box" style="background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);">
                        <i class="fas fa-check" style="color: #22c55e;"></i>
                        <span style="color: #22c55e; font-weight: 600;">Mật khẩu khớp</span>
                    </div>
                `;
            } else {
                matchText.innerHTML = `
                    <div class="settings-info-box" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3);">
                        <i class="fas fa-times" style="color: #ef4444;"></i>
                        <span style="color: #ef4444; font-weight: 600;">Mật khẩu không khớp</span>
                    </div>
                `;
            }
        }

        // Enhanced form validation with glass morphism alerts
        function validateForm() {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (!currentPassword) {
                showGlassAlert('Vui lòng nhập mật khẩu hiện tại!', 'error');
                document.getElementById('current_password').focus();
                return false;
            }

            if (!newPassword) {
                showGlassAlert('Vui lòng nhập mật khẩu mới!', 'error');
                document.getElementById('new_password').focus();
                return false;
            }

            if (newPassword.length < 6) {
                showGlassAlert('Mật khẩu mới phải có ít nhất 6 ký tự!', 'error');
                document.getElementById('new_password').focus();
                return false;
            }

            if (newPassword !== confirmPassword) {
                showGlassAlert('Mật khẩu mới và xác nhận mật khẩu không khớp!', 'error');
                document.getElementById('confirm_password').focus();
                return false;
            }

            if (currentPassword === newPassword) {
                showGlassAlert('Mật khẩu mới phải khác mật khẩu hiện tại!', 'error');
                document.getElementById('new_password').focus();
                return false;
            }

            return true;
        }

        // Enhanced event listeners and interactions
        const newPasswordField = document.getElementById('new_password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const form = document.getElementById('changePasswordForm');
        const submitBtn = document.getElementById('submitBtn');

        // Password field interactions
        if (newPasswordField) {
            newPasswordField.addEventListener('input', updatePasswordStrength);
            newPasswordField.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(var(--primary-rgb), 0.15)';
            });
            newPasswordField.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
            });
        }

        if (confirmPasswordField) {
            confirmPasswordField.addEventListener('input', checkPasswordMatch);
            confirmPasswordField.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(var(--primary-rgb), 0.15)';
            });
            confirmPasswordField.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
            });
        }

        // Enhanced form submission
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return;
                }

                // Show loading state
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Đang xử lý...</span>';
                }
            });
        }

        // Enhanced form field interactions
        const formFields = document.querySelectorAll('.form-glass');
        formFields.forEach(field => {
            field.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(var(--primary-rgb), 0.15)';
            });

            field.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
            });
        });
    });

    // Glass morphism alert function
    function showGlassAlert(message, type = 'info') {
        const alertColors = {
            'error': 'rgba(239, 68, 68, 0.1)',
            'success': 'rgba(34, 197, 94, 0.1)',
            'info': 'rgba(59, 130, 246, 0.1)'
        };

        const iconColors = {
            'error': '#ef4444',
            'success': '#22c55e',
            'info': '#3b82f6'
        };

        const icons = {
            'error': 'fas fa-exclamation-circle',
            'success': 'fas fa-check-circle',
            'info': 'fas fa-info-circle'
        };

        const alert = document.createElement('div');
        alert.className = 'glass-container position-fixed';
        alert.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: ${alertColors[type]};
            border-color: ${iconColors[type]}40;
            padding: 1rem 1.5rem;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
        `;

        alert.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="${icons[type]} me-3" style="color: ${iconColors[type]};"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        document.body.appendChild(alert);

        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }
</script>

<style>
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .form-glass:focus {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Mobile responsive enhancements */
    @media (max-width: 768px) {
        .glass-grid-2 {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .profile-hero {
            padding: 2rem 0 1.5rem;
        }

        .profile-info h2 {
            font-size: 1.5rem;
        }

        .settings-card {
            padding: 1.5rem;
            border-radius: 16px;
        }

        .btn-glass {
            width: 100%;
            justify-content: center;
        }

        .row .col-md-6 {
            margin-bottom: 1rem;
        }
    }

    @media (max-width: 480px) {
        .profile-container {
            padding: 0 1rem;
        }

        .glass-container {
            border-radius: 12px;
        }

        .settings-card {
            padding: 1.25rem;
        }

        .glass-icon-lg {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }
    }
</style>

</body>
</html>
