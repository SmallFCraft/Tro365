<?php

namespace Tro365\Core;

use Exception;
use Tro365\Models\User;
use Tro365\Activity;
use Delight\Auth\Auth as DelightAuth;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\UserAlreadyExistsException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\NotLoggedInException;
use Delight\Auth\EmailNotVerifiedException;

/**
 * Authentication Class - Enhanced with delight-im/auth
 * Tro365 - Website thuê trọ
 */
class Auth
{
    private $user;
    private ?DelightAuth $delightAuth = null;
    private bool $useEnhancedAuth = false;

    public function __construct()
    {
        $this->user = new User();

        // Try to initialize delight-im/auth
        try {
            $pdo = $this->getDatabaseConnection();
            $this->delightAuth = new DelightAuth($pdo);
            $this->useEnhancedAuth = true;
            writeLog("Auth: Enhanced authentication enabled with delight-im/auth");
        } catch (Exception $e) {
            writeLog("Auth: Using legacy authentication - " . $e->getMessage());
            $this->useEnhancedAuth = false;
        }
        // Auto-login from remember cookie if session not established yet
        if (!$this->isLoggedIn()) {
            $this->autoLoginFromCookie();
        }

    }

    /**
     * Login user
     */
    public function login($username, $password, $remember = false)
    {
        try {
            $userData = $this->user->verifyLogin($username, $password);

            // Check email verification if required
            if (isEmailVerificationRequired() && !$this->user->isEmailVerified($userData['ID'])) {
                throw new Exception("Vui lòng xác thực email trước khi đăng nhập. Kiểm tra hộp thư của bạn.");
            }

            // Set session data
            $this->setSessionData($userData);

            // Set remember me cookie if requested
            if ($remember) {
                $this->setRememberMeCookie($userData['ID']);
            }

            // Log login attempt
            $this->logLoginAttempt($userData['ID'], true);

            // Log activity
            try {
                $activity = new Activity();
                $activity->logLogin($userData['ID']);
            } catch (Exception $e) {
                // Silent fail for activity logging
                writeLog("Activity log error: " . $e->getMessage());
            }

            return $userData;

        } catch (Exception $e) {
            // Log failed login attempt - try to find user ID first
            try {
                $user = new User();
                $userData = $user->getByUsername($username) ?: $user->getByEmail($username);
                $userId = $userData ? $userData['ID'] : null;

                if ($userId) {
                    $this->logLoginAttempt($userId, false, $username);
                }
            } catch (Exception $logError) {
                // If we can't log, just continue
                error_log("Failed to log failed login attempt: " . $logError->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        // Log logout activity before clearing session
        if ($this->isLoggedIn()) {
            try {
                $activity = new Activity();
                $activity->logLogout($_SESSION['user_id']);
            } catch (Exception $e) {
                // Silent fail for activity logging
                writeLog("Activity log error: " . $e->getMessage());
            }
        }

        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
            unset($_COOKIE['remember_token']);
        }

        // Clear session
        session_unset();
        session_destroy();

        // Start new session
        session_start();

        // Regenerate CSRF token
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }

    /**
     * Register new user
     */
    public function register($data)
    {
        try {
            // Validate data
            $this->validateRegistrationData($data);

            // Create user
            $userId = $this->user->create($data);
            $userData = $this->user->getById($userId);

            // Check if email verification is required
            if (isEmailVerificationRequired()) {
                // Generate verification token
                $token = $this->user->generateEmailVerificationToken($userId);

                // Send verification email
                $verificationLink = app_url("/verify-email?token=" . $token);
                $emailSent = sendEmailVerification($data['Email'], $data['HoTen'], $verificationLink, $token);

                if ($emailSent) {
                    writeLog("Email verification sent to: " . $data['Email']);
                } else {
                    writeLog("Failed to send email verification to: " . $data['Email']);
                }

                // Don't auto login if email verification is required
                // User needs to verify email first
                return [
                    'user' => $userData,
                    'requires_verification' => true,
                    'message' => 'Vui lòng kiểm tra email để xác thực tài khoản'
                ];
            } else {
                // Auto login after registration (existing behavior)
                $this->setSessionData($userData);
                return [
                    'user' => $userData,
                    'requires_verification' => false
                ];
            }

        } catch (Exception $e) {
            throw new Exception("Lỗi đăng ký: " . $e->getMessage());
        }
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get current user data with auto-refresh
     */
    public function getCurrentUser($forceRefresh = false)
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        // Auto-refresh session data every 5 minutes or on force refresh
        $lastRefresh = $_SESSION['last_refresh'] ?? 0;
        $shouldRefresh = $forceRefresh || (time() - $lastRefresh) > 300; // 5 minutes

        if ($shouldRefresh) {
            $this->refreshSessionData();
        }

        return [
            'ID' => $_SESSION['user_id'],
            'TenDN' => $_SESSION['user_username'],
            'Email' => $_SESSION['user_email'],
            'HoTen' => $_SESSION['user_name'],
            'NgaySinh' => $_SESSION['user_birth_date'] ?? '',
            'GioiTinh' => $_SESSION['user_gender'] ?? '',
            'CCCD' => $_SESSION['user_cccd'] ?? '',
            'SDT' => $_SESSION['user_phone'] ?? '',
            'DiaChi' => $_SESSION['user_address'] ?? '',
            'VaiTroID' => $_SESSION['user_role'],
            'TenVT' => $_SESSION['user_role_name'],
            'TrangThai' => $_SESSION['user_status'] ?? 1,
            'AnhDaiDien' => $_SESSION['user_avatar'],
            'email_verified_at' => $_SESSION['user_email_verified_at'] ?? null
        ];
    }

    /**
     * Refresh session data from database
     */
    private function refreshSessionData()
    {
        try {
            // Get fresh user data from database
            $userData = $this->user->getById($_SESSION['user_id']);

            if ($userData) {
                // Update session with fresh data
                $this->setSessionData($userData);
                $_SESSION['last_refresh'] = time();
                writeLog("Session refreshed for user ID: " . $_SESSION['user_id']);
            }
        } catch (Exception $e) {
            writeLog("Session refresh error: " . $e->getMessage());
        }
    }

    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return $_SESSION['user_role'] >= $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->hasRole(ROLE_ADMIN);
    }

    /**
     * Check if user is seller
     */
    public function isSeller()
    {
        return $this->hasRole(ROLE_SELLER);
    }

    /**
     * Check if user is moderator or higher
     */
    public function isModerator()
    {
        return $this->hasRole(ROLE_MODERATOR);
    }

    /**
     * Check if user is supporter or higher
     */
    public function isSupporter()
    {
        return $this->hasRole(ROLE_SUPPORTER);
    }

    /**
     * Require login
     */
    public function requireLogin($redirectUrl = null)
    {
        if (!$this->isLoggedIn()) {
            $redirectUrl = $redirectUrl ?: app_url('login');
            redirect($redirectUrl);
        }
    }

    /**
     * Login user directly with user data (for email verification, etc.)
     */
    public function loginUser($userData)
    {
        try {
            // Set session data
            $this->setSessionData($userData);

            // Log activity
            try {
                $activity = new Activity();
                $activity->logLogin($userData['ID']);
            } catch (Exception $e) {
                // Silent fail for activity logging
                writeLog("Activity log error: " . $e->getMessage());
            }

            return true;

        } catch (Exception $e) {
            throw new Exception("Lỗi đăng nhập: " . $e->getMessage());
        }
    }

    /**
     * Require role
     */
    public function requireRole($role, $redirectUrl = null)
    {
        $this->requireLogin();

        if (!$this->hasRole($role)) {
            $redirectUrl = $redirectUrl ?: app_url();
            setFlashMessage(MSG_ERROR, 'Bạn không có quyền truy cập trang này');
            redirect($redirectUrl);
        }
    }

    /**
     * Require admin
     */
    public function requireAdmin($redirectUrl = null)
    {
        $this->requireRole(ROLE_ADMIN, $redirectUrl);
    }

    /**
     * Require seller
     */
    public function requireSeller($redirectUrl = null)
    {
        $this->requireRole(ROLE_SELLER, $redirectUrl);
    }

    /**
     * Require moderator or higher
     */
    public function requireModerator($redirectUrl = null)
    {
        $this->requireRole(ROLE_MODERATOR, $redirectUrl);
    }

    /**
     * Require supporter or higher
     */
    public function requireSupporter($redirectUrl = null)
    {
        $this->requireRole(ROLE_SUPPORTER, $redirectUrl);
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken($email)
    {
        try {
            $userData = $this->user->getByEmail($email);
            if (!$userData) {
                throw new Exception("Email không tồn tại trong hệ thống");
            }

            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $db = Database::getInstance();
            $db->insert('TokenResetPassword', [
                'KhachHangID' => $userData['ID'],
                'Token' => $token,
                'NgayHetHan' => $expiry
            ]);

            return $token;

        } catch (Exception $e) {
            throw new Exception("Lỗi tạo token reset: " . $e->getMessage());
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword)
    {
        try {
            $db = Database::getInstance();

            $sql = "SELECT * FROM TokenResetPassword
                    WHERE Token = :token
                    AND NgayHetHan > NOW()
                    AND DaSuDung = 0";

            $tokenData = $db->selectOne($sql, ['token' => $token]);

            if (!$tokenData) {
                throw new Exception("Token không hợp lệ hoặc đã hết hạn");
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->user->update($tokenData['KhachHangID'], ['MatKhau' => $hashedPassword]);

            // Mark token as used
            $db->update('TokenResetPassword',
                ['DaSuDung' => 1],
                'ID = :id',
                ['id' => $tokenData['ID']]
            );

            return true;

        } catch (Exception $e) {
            throw new Exception("Lỗi reset mật khẩu: " . $e->getMessage());
        }
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData($data)
    {
        $errors = [];

        // Required fields
        $required = ['TenDN', 'Email', 'MatKhau', 'HoTen'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Trường {$field} là bắt buộc";
            }
        }

        // Username validation
        if (!empty($data['TenDN'])) {
            if (strlen($data['TenDN']) < MIN_USERNAME_LENGTH) {
                $errors[] = "Tên đăng nhập phải có ít nhất " . MIN_USERNAME_LENGTH . " ký tự";
            }
            if (strlen($data['TenDN']) > MAX_USERNAME_LENGTH) {
                $errors[] = "Tên đăng nhập không được quá " . MAX_USERNAME_LENGTH . " ký tự";
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['TenDN'])) {
                $errors[] = "Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới";
            }
        }

        // Email validation
        if (!empty($data['Email']) && !isValidEmail($data['Email'])) {
            $errors[] = "Email không hợp lệ";
        }

        // Password validation
        if (!empty($data['MatKhau'])) {
            if (strlen($data['MatKhau']) < MIN_PASSWORD_LENGTH) {
                $errors[] = "Mật khẩu phải có ít nhất " . MIN_PASSWORD_LENGTH . " ký tự";
            }
            if (strlen($data['MatKhau']) > MAX_PASSWORD_LENGTH) {
                $errors[] = "Mật khẩu không được quá " . MAX_PASSWORD_LENGTH . " ký tự";
            }
        }

        // Phone validation
        if (!empty($data['SDT']) && !isValidPhone($data['SDT'])) {
            $errors[] = "Số điện thoại không hợp lệ";
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
    }

    /**
     * Set session data
     */
    private function setSessionData($userData)
    {
        $_SESSION['user_id'] = $userData['ID'];
        $_SESSION['user_username'] = $userData['TenDN'];
        $_SESSION['user_email'] = $userData['Email'];
        $_SESSION['user_name'] = $userData['HoTen'];
        $_SESSION['user_birth_date'] = $userData['NgaySinh'] ?? '';
        $_SESSION['user_gender'] = $userData['GioiTinh'] ?? '';
        $_SESSION['user_cccd'] = $userData['CCCD'] ?? '';
        $_SESSION['user_phone'] = $userData['SDT'] ?? '';
        $_SESSION['user_address'] = $userData['DiaChi'] ?? '';
        $_SESSION['user_role'] = $userData['VaiTroID'];
        $_SESSION['user_role_name'] = $userData['TenVT'] ?? getRoleName($userData['VaiTroID']);
        $_SESSION['user_status'] = $userData['TrangThai']; // Add user status
        $_SESSION['user_avatar'] = $userData['AnhDaiDien'];
        $_SESSION['user_email_verified_at'] = $userData['email_verified_at'] ?? null; // Add email verification status
        $_SESSION['login_time'] = time();
    }

    /**
     * Update session with fresh user data (public method)
     */
    public function updateSession()
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $this->refreshSessionData();
        return true;
    }

    /**
     * Set remember me cookie
     */
    private function setRememberMeCookie($userId)
    {
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        $token = $this->generateRememberToken((int)$userId, $expiry);

        $cookieOptions = [
            'expires'  => $expiry,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        $result = setcookie('remember_token', $token, $cookieOptions);

        if (!$result || headers_sent()) {
            writeLog("Auth: Failed to set remember me cookie", 'warning', 'auth', [
                'user_id' => $userId,
                'headers_sent' => headers_sent()
            ]);
        }
    }

    /**
     * Generate signed remember token (userId|expiry|nonce|hmac)
     */
    private function generateRememberToken(int $userId, int $expiry): string
    {
        $nonce = bin2hex(random_bytes(16));
        $payload = $userId . '|' . $expiry . '|' . $nonce;
        $sig = hash_hmac('sha256', $payload, APP_KEY);
        return base64_encode($payload . '|' . $sig);
    }

    /**
     * Validate remember token, return userId if valid, otherwise null
     */
    private function validateRememberToken(string $token): ?int
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            return null;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 4) {
            return null;
        }

        [$userId, $expiry, $nonce, $sig] = $parts;
        if (!ctype_digit($userId) || !ctype_digit($expiry)) {
            return null;
        }

        if ((int)$expiry < time()) {
            return null;
        }

        $payload = $userId . '|' . $expiry . '|' . $nonce;
        $expected = hash_hmac('sha256', $payload, APP_KEY);
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        return (int)$userId;
    }

    /**
     * Attempt auto-login using remember cookie (sliding expiration)
     */
    private function autoLoginFromCookie(): void
    {
        if ($this->isLoggedIn()) {
            return;
        }
        if (empty($_COOKIE['remember_token'])) {
            return;
        }

        $userId = $this->validateRememberToken($_COOKIE['remember_token']);
        if (!$userId) {
            return;
        }

        $userData = $this->user->getById($userId);
        if (!$userData) {
            return;
        }

        $this->setSessionData($userData);

        // Sliding expiration: refresh cookie
        $this->setRememberMeCookie($userId);

        writeLog("Auth: Auto-login successful from remember me cookie", 'info', 'auth', ['user_id' => $userId]);
    }

    /**
     * Log login attempt
     */
    private function logLoginAttempt($userId, $success, $username = null)
    {
        try {
            $db = Database::getInstance();
            $db->insert('LichSuDangNhap', [
                'KhachHangID' => $userId,
                'DiaChiIP' => $_SERVER['REMOTE_ADDR'] ?? '',
                'UserAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'TrangThai' => $success ? 1 : 0
            ]);
        } catch (Exception $e) {
            // Log error but don't throw exception
            error_log("Failed to log login attempt: " . $e->getMessage());
        }
    }

    /**
     * Get database connection for delight-im/auth
     * UNIFIED: Now uses Database::getInstance() for single source of truth
     */
    private function getDatabaseConnection(): \PDO
    {
        // Use unified Database connection to ensure consistency
        // This eliminates config drift between auth and app layers
        return Database::getInstance()->getConnection();
    }

    /**
     * Enhanced login using delight-im/auth (with fallback)
     */
    public function enhancedLogin(string $email, string $password, bool $remember = false): array
    {
        if (!$this->useEnhancedAuth) {
            // Fallback to legacy login
            try {
                $userData = $this->login($email, $password, $remember);
                return [
                    'success' => true,
                    'user' => $userData,
                    'message' => 'Đăng nhập thành công!'
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        try {
            writeLog("Auth: Enhanced login attempt - Email: $email");

            // Use delight-im/auth for login
            $this->delightAuth->login($email, $password, $remember ? (60 * 60 * 24 * 30) : null);

            // Get user data and sync with Tro365 session
            $userId = $this->delightAuth->getUserId();
            $userData = $this->user->getById($userId);

            if ($userData) {
                $this->setSessionData($userData);
                writeLog("Auth: Enhanced login successful - User ID: $userId");

                return [
                    'success' => true,
                    'user' => $userData,
                    'message' => 'Đăng nhập thành công!'
                ];
            } else {
                throw new Exception('Không tìm thấy thông tin người dùng');
            }

        } catch (InvalidEmailException $e) {
            writeLog("Auth: Enhanced login failed - Invalid email: $email");
            return [
                'success' => false,
                'error' => 'Email không hợp lệ'
            ];
        } catch (InvalidPasswordException $e) {
            writeLog("Auth: Enhanced login failed - Invalid password");
            return [
                'success' => false,
                'error' => 'Email hoặc mật khẩu không đúng'
            ];
        } catch (EmailNotVerifiedException $e) {
            writeLog("Auth: Enhanced login failed - Email not verified: $email");
            return [
                'success' => false,
                'error' => 'Vui lòng xác thực email trước khi đăng nhập'
            ];
        } catch (TooManyRequestsException $e) {
            writeLog("Auth: Enhanced login failed - Too many requests");
            return [
                'success' => false,
                'error' => 'Quá nhiều yêu cầu đăng nhập. Vui lòng thử lại sau.'
            ];
        } catch (Exception $e) {
            writeLog("Auth: Enhanced login exception - " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra khi đăng nhập'
            ];
        }
    }

    /**
     * Enhanced register using delight-im/auth (with fallback)
     */
    public function enhancedRegister(string $email, string $password, ?string $username = null, array $additionalData = []): array
    {
        if (!$this->useEnhancedAuth) {
            // Fallback to legacy registration - will implement this properly
            return [
                'success' => false,
                'error' => 'Enhanced registration not available, please use legacy registration'
            ];
        }

        try {
            writeLog("Auth: Enhanced registration attempt - Email: $email, Username: $username");

            // Use delight-im/auth for registration
            $userId = $this->delightAuth->register($email, $password, $username);

            // Add additional user data to Tro365 tables if provided
            if (!empty($additionalData)) {
                $additionalData['ID'] = $userId;
                $additionalData['Email'] = $email;
                $additionalData['TenDN'] = $username ?: explode('@', $email)[0];
                $this->user->create($additionalData);
            }

            writeLog("Auth: Enhanced registration successful - User ID: $userId");

            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'Đăng ký thành công!'
            ];

        } catch (InvalidEmailException $e) {
            writeLog("Auth: Enhanced registration failed - Invalid email: $email");
            return [
                'success' => false,
                'error' => 'Email không hợp lệ'
            ];
        } catch (InvalidPasswordException $e) {
            writeLog("Auth: Enhanced registration failed - Invalid password");
            return [
                'success' => false,
                'error' => 'Mật khẩu không hợp lệ (tối thiểu 6 ký tự)'
            ];
        } catch (UserAlreadyExistsException $e) {
            writeLog("Auth: Enhanced registration failed - User already exists: $email");
            return [
                'success' => false,
                'error' => 'Email đã được sử dụng'
            ];
        } catch (TooManyRequestsException $e) {
            writeLog("Auth: Enhanced registration failed - Too many requests");
            return [
                'success' => false,
                'error' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.'
            ];
        } catch (Exception $e) {
            writeLog("Auth: Enhanced registration exception - " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Có lỗi xảy ra khi đăng ký'
            ];
        }
    }

    /**
     * Check if enhanced auth is available
     */
    public function isEnhancedAuthAvailable(): bool
    {
        return $this->useEnhancedAuth;
    }
}
