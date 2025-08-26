<?php
/**
 * Register Page
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect(app_url());
}

// Check if registration is enabled
if (!isRegistrationEnabled()) {
    redirect('/');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }

        // Enhanced validation using rakit/validation
        $formData = [
            'fullname' => cleanInput($_POST['fullname'] ?? ''),
            'username' => cleanInput($_POST['username'] ?? ''),
            'email' => cleanInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirm'] ?? '',
            'phone' => cleanInput($_POST['phone'] ?? '')
        ];

        $validationResult = \Tro365\Helpers\ValidationHelper::validateRegistrationForm($formData);
        if (!$validationResult['valid']) {
            $errors = [];
            foreach ($validationResult['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            throw new Exception(implode(', ', $errors));
        }

        // Validate terms acceptance
        if (!isset($_POST['terms'])) {
            throw new Exception('Bạn phải đồng ý với điều khoản sử dụng');
        }

        // Prepare data for registration
        $data = [
            'TenDN' => $formData['username'],
            'Email' => $formData['email'],
            'MatKhau' => $formData['password'],
            'HoTen' => $formData['fullname'],
            'SDT' => $formData['phone']
        ];

        // Use legacy registration for now (enhanced auth needs database setup)
        writeLog("Register: Using legacy authentication");
        $result = $auth->register($data);

        // Handle different registration outcomes
        if (isset($result['requires_verification']) && $result['requires_verification']) {
            // Email verification is required
            setFlashMessage(MSG_INFO, 'Đăng ký thành công! Vui lòng kiểm tra email để xác thực tài khoản trước khi đăng nhập.');
            redirect('/login');
        } else {
            // Normal registration (no email verification required)
            // Send welcome email using helper function
            try {
                $emailSent = sendWelcomeEmail($data['Email'], $data['HoTen']);

                if ($emailSent) {
                    writeLog("Welcome email sent to new user: " . $data['Email']);
                } else {
                    writeLog("Failed to send welcome email to: " . $data['Email']);
                }
            } catch (Exception $e) {
                // Log error but don't fail registration
                writeLog("Failed to send welcome email to: " . $data['Email'] . " - Error: " . $e->getMessage());
            }

            setFlashMessage(MSG_SUCCESS, 'Đăng ký thành công! Chào mừng bạn đến với ' . getWebsiteName() . '. Vui lòng kiểm tra email để nhận thông tin chào mừng.');
            redirect('/');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Đăng ký - <?= getWebsiteName() ?></title>
    <meta name="description" content="Đăng ký tài khoản <?= getWebsiteName() ?> để tìm kiếm và đăng tin phòng trọ">
    <meta name="keywords" content="đăng ký, tro365, phòng trọ, thuê trọ, tạo tài khoản">
    <meta name="theme-color" content="#667eea" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1a1d29" media="(prefers-color-scheme: dark)">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="<?= app_url('assets/css/client/main.css') ?>" as="style">
    <link rel="preload" href="<?= app_url('assets/css/client/auth.css') ?>" as="style">
    <link rel="preload" href="<?= app_url('assets/css/client/layouts.css') ?>" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
    
    <!-- Modern Assets Integration -->
    <?php
    require_once __DIR__ . '/../../includes/modern-assets.php';
    addModernMetaTags();
    loadModernCSS();
    ?>

    <!-- Critical CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Project CSS -->
    <link href="<?= app_url('assets/css/client/main.css') ?>" rel="stylesheet">
    <link href="<?= app_url('assets/css/client/auth.css') ?>" rel="stylesheet">
    <link href="<?= app_url('assets/css/client/layouts.css') ?>" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= app_url('assets/images/logo/favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?= app_url('assets/images/logo/apple-touch-icon.png') ?>">
</head>
    <style>
        .auth-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .auth-body {
            padding: 2rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        .strength-weak { background-color: #dc3545; }
        .strength-medium { background-color: #ffc107; }
        .strength-strong { background-color: #28a745; }
    </style>
</head>
<body class="auth-page">
    <!-- Auth Page Enhanced Container -->
    <div class="auth-page-enhanced">
        <!-- Main Content Container -->
        <div class="auth-layout-container">
            <!-- Main Auth Card -->
            <div class="auth-main-content">
                <div class="auth-card-enhanced animate-fade-in-up">
                    <!-- Enhanced Header -->
                    <div class="auth-header-enhanced">
                        <i class="fas fa-user-plus auth-icon"></i>
                        <h1>Đăng ký tài khoản</h1>
                        <p>Tạo tài khoản để bắt đầu tìm phòng trọ lý tưởng</p>
                    </div>
                    
                    <!-- Enhanced Body -->
                    <div class="auth-body-enhanced">
                        <!-- Enhanced Status Messages -->
                        <?php if ($error): ?>
                            <div class="auth-status-enhanced error">
                                <i class="fas fa-exclamation-circle status-icon"></i>
                                <h5>Đăng ký thất bại</h5>
                                <p><?= e($error) ?></p>
                            </div>
                        <?php endif; ?>
                
                        <!-- Enhanced Registration Form -->
                        <form method="POST" action="/register" class="auth-form-enhanced needs-validation" id="registerForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <!-- Full Name Field -->
                            <div class="auth-form-group-enhanced">
                                <label for="fullname" class="auth-form-label-enhanced">
                                    <i class="fas fa-id-card"></i>
                                    Họ và tên <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control-enhanced"
                                       id="fullname"
                                       name="fullname"
                                       value="<?= e($_POST['fullname'] ?? '') ?>"
                                       placeholder="Nhập họ và tên đầy đủ"
                                       minlength="2"
                                       maxlength="100"
                                       autocomplete="name"
                                       required>
                                <div class="invalid-feedback">Vui lòng nhập họ và tên</div>
                            </div>
                            
                            <!-- Username and Email Row -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="auth-form-group-enhanced">
                                        <label for="username" class="auth-form-label-enhanced">
                                            <i class="fas fa-user"></i>
                                            Tên đăng nhập <span class="text-danger">*</span>
                                        </label>
                                        <input type="text"
                                               class="form-control-enhanced"
                                               id="username"
                                               name="username"
                                               value="<?= e($_POST['username'] ?? '') ?>"
                                               placeholder="Nhập tên đăng nhập"
                                               pattern="[a-zA-Z0-9_]{3,30}"
                                               data-pattern-message="Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới (3-30 ký tự)"
                                               minlength="3"
                                               maxlength="30"
                                               autocomplete="username"
                                               required>
                                        <div id="usernameFeedback" class="invalid-feedback"></div>
                                        <div class="auth-form-text">3-30 ký tự, chỉ chữ cái, số và dấu gạch dưới</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="auth-form-group-enhanced">
                                        <label for="email" class="auth-form-label-enhanced">
                                            <i class="fas fa-envelope"></i>
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email"
                                               class="form-control-enhanced"
                                               id="email"
                                               name="email"
                                               value="<?= e($_POST['email'] ?? '') ?>"
                                               placeholder="Nhập địa chỉ email"
                                               autocomplete="email"
                                               required>
                                        <div id="emailFeedback" class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Phone Field -->
                            <div class="auth-form-group-enhanced">
                                <label for="phone" class="auth-form-label-enhanced">
                                    <i class="fas fa-phone"></i>
                                    Số điện thoại
                                </label>
                                <input type="tel"
                                       class="form-control-enhanced"
                                       id="phone"
                                       name="phone"
                                       value="<?= e($_POST['phone'] ?? '') ?>"
                                       placeholder="Nhập số điện thoại"
                                       pattern="[0-9]{10,11}"
                                       data-pattern-message="Số điện thoại phải có 10-11 chữ số"
                                       autocomplete="tel"
                                       title="Số điện thoại phải có 10-11 chữ số">
                                <div class="invalid-feedback"></div>
                            </div>
                            
                            <!-- Password Fields Row -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="auth-form-group-enhanced">
                                        <label for="password" class="auth-form-label-enhanced">
                                            <i class="fas fa-lock"></i>
                                            Mật khẩu <span class="text-danger">*</span>
                                        </label>
                                        <div class="auth-input-group-enhanced">
                                            <input type="password"
                                                   class="form-control-enhanced form-glass"
                                                   id="password"
                                                   name="password"
                                                   placeholder="Nhập mật khẩu"
                                                   minlength="6"
                                                   autocomplete="new-password"
                                                   required>
                                            <button type="button" class="auth-input-toggle" onclick="togglePasswordVisibility('password', 'toggleIcon1')" aria-label="Hiện/ẩn mật khẩu">
                                                <i class="fas fa-eye" id="toggleIcon1"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength-container">
                                            <div class="password-strength" id="passwordStrength"></div>
                                            <div class="password-strength-text" id="passwordStrengthText"></div>
                                        </div>
                                        <div class="auth-form-text">Tối thiểu 6 ký tự, nên có chữ hoa, chữ thường, số và ký tự đặc biệt</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="auth-form-group-enhanced">
                                        <label for="password_confirm" class="auth-form-label-enhanced">
                                            <i class="fas fa-lock"></i>
                                            Xác nhận mật khẩu <span class="text-danger">*</span>
                                        </label>
                                        <div class="auth-input-group-enhanced">
                                            <input type="password"
                                                   class="form-control-enhanced form-glass"
                                                   id="password_confirm"
                                                   name="password_confirm"
                                                   placeholder="Nhập lại mật khẩu"
                                                   autocomplete="new-password"
                                                   required>
                                            <button type="button" class="auth-input-toggle" onclick="togglePasswordVisibility('password_confirm', 'toggleIcon2')" aria-label="Hiện/ẩn mật khẩu">
                                                <i class="fas fa-eye" id="toggleIcon2"></i>
                                            </button>
                                        </div>
                                        <div id="confirmPasswordFeedback" class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="auth-form-group-enhanced">
                                <div class="form-check d-flex align-items-start gap-3">
                                    <input type="checkbox" class="form-check-input mt-1" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Tôi đồng ý với 
                                        <a href="/terms" class="text-decoration-none" style="color: var(--primary-color);" target="_blank">Điều khoản sử dụng</a> 
                                        và 
                                        <a href="/privacy" class="text-decoration-none" style="color: var(--primary-color);" target="_blank">Chính sách bảo mật</a>
                                    </label>
                                </div>
                                <div class="invalid-feedback">Bạn phải đồng ý với điều khoản sử dụng</div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="auth-form-group-enhanced">
                                <button type="submit" class="btn-enhanced btn-enhanced-primary w-100">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Đăng ký tài khoản</span>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Enhanced Progress Bar -->
                        <div class="auth-progress-enhanced">
                            <div class="progress-bar" style="width: 0%"></div>
                        </div>
                        
                        <!-- Enhanced Navigation Links -->
                        <div class="text-center mt-4">
                            <div class="d-flex flex-column gap-3">
                                <div class="auth-form-text text-center">
                                    Đã có tài khoản? 
                                    <a href="<?= app_url('login') ?>" class="text-decoration-none fw-bold ms-2" style="color: var(--primary-color);">
                                        Đăng nhập ngay
                                    </a>
                                </div>
                                
                                <a href="<?= app_url() ?>" class="btn-enhanced btn-enhanced-secondary">
                                    <i class="fas fa-home"></i>
                                    <span>Về trang chủ</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern JavaScript Libraries -->
    <?php
    require_once __DIR__ . '/../../includes/modern-assets.php';
    loadModernJS();
    // Skip modern form validation for auth pages to avoid conflicts
    // initModernFormValidation('#registerForm');
    ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('assets/js/common.js') ?>" defer></script>
    <script src="<?= app_url('assets/js/client/auth.js') ?>" defer></script>
    
    <!-- Enhanced JavaScript Functions -->
    <script>
        // Enhanced Password Toggle Function
        function togglePasswordVisibility(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }
        
        // Progress Bar Animation during Form Submission
        document.getElementById('registerForm').addEventListener('submit', function() {
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = '100%';
            }
        });
        
        // Auto-focus on first input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const fullnameField = document.getElementById('fullname');
            if (fullnameField && !fullnameField.value) {
                fullnameField.focus();
            }
        });
        
        // Enhanced Theme Integration
        if (typeof window.Tro365Auth !== 'undefined') {
            document.addEventListener('DOMContentLoaded', function() {
                // The auth.js will handle theme detection, form validation, and initialization
                console.log('Enhanced Registration System Loaded');
            });
        }

        // Backup Password Strength Function
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for auth.js to load
            setTimeout(function() {
                const passwordField = document.getElementById('password');
                const strengthBar = document.getElementById('passwordStrength');
                const strengthText = document.getElementById('passwordStrengthText');

                if (passwordField && strengthBar && strengthText) {
                    console.log('✅ Password strength elements found, initializing backup...');

                    // Remove existing listeners if any
                    passwordField.removeEventListener('input', window.passwordStrengthHandler);

                    // Create new handler
                    window.passwordStrengthHandler = function() {
                        const password = this.value;
                        let strength = 0;

                        // Calculate strength
                        if (password.length >= 6) strength++;
                        if (/[a-z]/.test(password)) strength++;
                        if (/[A-Z]/.test(password)) strength++;
                        if (/[0-9]/.test(password)) strength++;
                        if (/[^A-Za-z0-9]/.test(password)) strength++;

                        // Reset classes
                        strengthBar.className = 'password-strength';

                        // Apply strength styling
                        if (password.length === 0) {
                            strengthText.textContent = '';
                            strengthText.className = 'password-strength-text';
                        } else if (strength <= 2) {
                            strengthBar.classList.add('strength-weak');
                            strengthText.textContent = 'Yếu';
                            strengthText.className = 'password-strength-text weak';
                        } else if (strength <= 4) {
                            strengthBar.classList.add('strength-medium');
                            strengthText.textContent = 'Trung bình';
                            strengthText.className = 'password-strength-text medium';
                        } else {
                            strengthBar.classList.add('strength-strong');
                            strengthText.textContent = 'Mạnh';
                            strengthText.className = 'password-strength-text strong';
                        }

                        console.log(`Password strength: ${strength}/5 - ${strengthText.textContent}`);
                    };

                    // Add event listener
                    passwordField.addEventListener('input', window.passwordStrengthHandler);

                    console.log('✅ Password strength backup initialized successfully');
                } else {
                    console.error('❌ Password strength elements not found:', {
                        passwordField: !!passwordField,
                        strengthBar: !!strengthBar,
                        strengthText: !!strengthText
                    });
                }
            }, 1000); // Wait 1 second for auth.js to load
        });
    </script>
</body>
</html>
