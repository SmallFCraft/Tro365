<?php
/**
 * Login Page
 * Tro365 - Website thuê trọ
 */

// Performance optimization includes
require_once __DIR__ . '/../../includes/performance/optimization.php';
require_once __DIR__ . '/../../classes/services/PerformanceOptimizationService.php';
require_once __DIR__ . '/../../classes/core/Auth.php';

use Tro365\Core\Auth;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect(app_url());
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
        $validationResult = \Tro365\Helpers\ValidationHelper::validateLoginForm($_POST);
        if (!$validationResult['valid']) {
            $errors = [];
            foreach ($validationResult['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            throw new Exception(implode(', ', $errors));
        }

        $username = cleanInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Use legacy authentication for now (enhanced auth needs database setup)
        writeLog("Login: Using legacy authentication");
        $user = $auth->login($username, $password, $remember);

        // Redirect based on role
        $redirectUrl = '/';
        if ($user['VaiTroID'] >= ROLE_ADMIN) {
            $redirectUrl = '/admin';
        } elseif ($user['VaiTroID'] >= ROLE_SELLER) {
            $redirectUrl = '/seller';
        }

        setFlashMessage(MSG_SUCCESS, 'Đăng nhập thành công!');
        redirect($redirectUrl);

    } catch (Exception $e) {
        $error = $e->getMessage();
        writeLog("Login error: " . $error);

        // Check if error is about email verification
        if (strpos($error, 'xác thực email') !== false) {
            $showResendLink = true;
            $lastUsername = $username; // Store username for resend link
        }
    }
}

// Get flash message
$flash = getFlashMessage();
$info = '';
if ($flash) {
    if ($flash['type'] === MSG_SUCCESS) {
        $success = $flash['message'];
    } elseif ($flash['type'] === MSG_INFO) {
        $info = $flash['message'];
    } else {
        $error = $flash['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Đăng nhập - <?= getWebsiteName() ?></title>
    <meta name="description" content="Đăng nhập vào <?= getWebsiteName() ?> để tìm kiếm và quản lý phòng trọ">
    <meta name="keywords" content="đăng nhập, tro365, phòng trọ, thuê trọ">
    <meta name="theme-color" content="#667eea" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1a1d29" media="(prefers-color-scheme: dark)">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="<?= app_url('assets/css/client/main.css') ?>" as="style">
    <link rel="preload" href="<?= app_url('assets/css/client/auth.css') ?>" as="style">
    <link rel="preload" href="<?= app_url('assets/css/client/layouts.css') ?>" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
    
    <!-- Global Debug Configuration -->
    <script>
        // Set global debug flag based on PHP debug setting
        window.TRO365_DEBUG = <?= isDebugModeEnabled() ? 'true' : 'false' ?>;

        // Override console.log if debug is disabled
        if (!window.TRO365_DEBUG) {
            const originalConsole = {
                log: console.log,
                warn: console.warn,
                error: console.error,
                info: console.info
            };

            // Only disable log and info, keep warn and error for important messages
            console.log = function() {};
            console.info = function() {};

            // Keep warn and error for important debugging
            // console.warn = function() {};
            // console.error = function() {};
        }
    </script>

    <!-- Modern Assets Integration (AssetManager) -->
    <?php
    $am = new \Tro365\Assets\AssetManager(app_url(''));
    $am->addMetaTags(['csrf' => csrf_token()]);
    echo $am->renderHead();
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
                        <i class="fas fa-sign-in-alt auth-icon"></i>
                        <h1>Đăng nhập</h1>
                        <p>Chào mừng bạn quay trở lại <?= getWebsiteName() ?>!</p>
                    </div>
                    
                    <!-- Enhanced Body -->
                    <div class="auth-body-enhanced">
                        <!-- Enhanced Status Messages -->
                        <?php if ($error): ?>
                            <div class="auth-status-enhanced error">
                                <i class="fas fa-exclamation-circle status-icon"></i>
                                <h5>Đăng nhập thất bại</h5>
                                <p><?= e($error) ?></p>
                                
                                <?php if (isset($showResendLink) && $showResendLink): ?>
                                    <div class="mt-3">
                                        <a href="<?= app_url('/resend-verification') ?>" class="btn-enhanced btn-enhanced-primary">
                                            <i class="fas fa-paper-plane"></i>
                                            Gửi lại email xác thực
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($info): ?>
                            <div class="auth-status-enhanced success">
                                <i class="fas fa-check-circle status-icon"></i>
                                <h5>Đăng ký thành công</h5>
                                <p><?= e($info) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="auth-status-enhanced success">
                                <i class="fas fa-check-circle status-icon"></i>
                                <h5>Thành công</h5>
                                <p><?= e($success) ?></p>
                            </div>
                        <?php endif; ?>
                
                        <!-- Enhanced Login Form -->
                        <form method="POST" action="/login" class="auth-form-enhanced needs-validation" id="loginForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <!-- Username/Email Field -->
                            <div class="auth-form-group-enhanced">
                                <label for="username" class="auth-form-label-enhanced">
                                    <i class="fas fa-user"></i>
                                    Tên đăng nhập hoặc Email
                                </label>
                                <input type="text" 
                                       class="form-control-enhanced" 
                                       id="username" 
                                       name="username" 
                                       value="<?= e($_POST['username'] ?? '') ?>"
                                       placeholder="Nhập tên đăng nhập hoặc email"
                                       autocomplete="username"
                                       required>
                                <div class="invalid-feedback">Vui lòng nhập tên đăng nhập hoặc email</div>
                            </div>
                            
                            <!-- Password Field -->
                            <div class="auth-form-group-enhanced">
                                <label for="password" class="auth-form-label-enhanced">
                                    <i class="fas fa-lock"></i>
                                    Mật khẩu
                                </label>
                                <div class="auth-input-group-enhanced">
                                    <input type="password"
                                           class="form-control-enhanced form-glass"
                                           id="password"
                                           name="password"
                                           placeholder="Nhập mật khẩu"
                                           autocomplete="current-password"
                                           required>
                                    <button type="button" class="auth-input-toggle" onclick="togglePasswordVisibility('password', 'toggleIcon')" aria-label="Hiện/ẩn mật khẩu">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                    <div class="invalid-feedback">Vui lòng nhập mật khẩu</div>
                                </div>
                            </div>
                            
                            <!-- Remember Me Checkbox -->
                            <div class="auth-form-group-enhanced">
                                <div class="form-check d-flex align-items-center gap-3">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        <i class="fas fa-clock me-2"></i>
                                        Ghi nhớ đăng nhập
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="auth-form-group-enhanced">
                                <button type="submit" class="btn-enhanced btn-enhanced-primary w-100">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>Đăng nhập</span>
                                </button>
                            </div>
                        </form>
                
                        <!-- Enhanced Navigation Links -->
                        <div class="auth-progress-enhanced">
                            <div class="progress-bar" style="width: 0%"></div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <div class="d-flex flex-column gap-3">
                                <a href="/forgot-password" class="btn-enhanced btn-enhanced-secondary">
                                    <i class="fas fa-key"></i>
                                    <span>Quên mật khẩu?</span>
                                </a>
                                
                                <div class="auth-form-text text-center">
                                    Chưa có tài khoản?
                                    <a href="/register" class="text-decoration-none fw-bold ms-2" style="color: var(--primary-color);">
                                        Đăng ký ngay
                                    </a>
                                </div>
                                
                                <a href="/" class="btn-enhanced btn-enhanced-secondary">
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

    <!-- Modern JavaScript Libraries (AssetManager) -->
    <?php
    $am = new \Tro365\Assets\AssetManager(app_url(''));
    echo $am->renderFooter();
    ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('assets/js/global/common.js') ?>" defer></script>
    <script src="<?= app_url('assets/js/client/auth.js') ?>" defer></script>

    <!-- Enhanced JavaScript Functions with Modern Integration -->
    <script>
        // Modern App Configuration
        window.Tro365Config = {
            csrfToken: '<?= csrf_token() ?>',
            appUrl: '<?= app_url() ?>',
            isLoggedIn: false,
            page: 'auth-login'
        };

        // Enhanced Password Toggle Function using DOM Utils
        function togglePasswordVisibility(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordField && toggleIcon) {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    toggleIcon.className = 'fas fa-eye-slash';
                } else {
                    passwordField.type = 'password';
                    toggleIcon.className = 'fas fa-eye';
                }
            } else {
                console.error('Toggle elements not found:', { passwordField, toggleIcon });
            }
        }

        // Modern Form Handling
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');

            if (loginForm) {
                // Progress Bar Animation during Form Submission
                loginForm.addEventListener('submit', function() {
                    const progressBar = document.querySelector('.progress-bar');
                    if (progressBar) {
                        progressBar.style.width = '100%';
                    }
                });

                // Auto-focus on first input when page loads
                const usernameField = document.getElementById('username');
                if (usernameField && !usernameField.value) {
                    usernameField.focus();
                }
            }

            // Modern Auth System Integration
            if (window.App) {
                console.log('🚀 Modern Auth System Loaded');

                // Skip modern form validation for auth pages to avoid conflicts
                // Legacy auth.js will handle form validation
                console.log('📝 Using legacy form validation for auth compatibility');
            } else {
                console.log('📝 Legacy Auth System Active');
            }
        });
    </script>

</body>
</html>
