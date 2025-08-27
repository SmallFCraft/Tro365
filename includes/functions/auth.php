<?php
/**
 * Authentication Functions
 * Tro365 - Website thuê trọ
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($roleId) {
    if (!isLoggedIn()) {
        return false;
    }

    return isset($_SESSION['user_role']) && $_SESSION['user_role'] >= $roleId;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole(ROLE_ADMIN);
}

/**
 * Check if user is moderator or higher
 */
function isModerator() {
    return hasRole(ROLE_MODERATOR);
}

/**
 * Check if user is supporter or higher
 */
function isSupporter() {
    return hasRole(ROLE_SUPPORTER);
}

/**
 * Check if user is seller or higher
 */
function isSeller() {
    return hasRole(ROLE_SELLER);
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin($redirectUrl = null) {
    if (!isLoggedIn()) {
        $redirectUrl = $redirectUrl ?: $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_after_login'] = $redirectUrl;
        header('Location: /auth/login');
        exit;
    }
}

/**
 * Require specific role - show 403 error if insufficient permissions
 */
function requireRole($roleId) {
    requireLogin();
    
    if (!hasRole($roleId)) {
        http_response_code(403);
        include_once __DIR__ . '/../layouts/client/header.php';
        echo '<div class="container mt-5 text-center">';
        echo '<h1>403 - Không có quyền truy cập</h1>';
        echo '<p>Bạn không có quyền truy cập vào trang này.</p>';
        echo '<a href="/" class="btn btn-primary">Về trang chủ</a>';
        echo '</div>';
        include_once __DIR__ . '/../layouts/client/footer.php';
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireRole(ROLE_ADMIN);
}

/**
 * Require moderator role or higher
 */
function requireModerator() {
    requireRole(ROLE_MODERATOR);
}

/**
 * Require supporter role or higher
 */
function requireSupporter() {
    requireRole(ROLE_SUPPORTER);
}

/**
 * Require seller role or higher
 */
function requireSeller() {
    requireRole(ROLE_SELLER);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    static $currentUser = null;
    
    if ($currentUser === null) {
        $user = new Tro365\User();
        $currentUser = $user->getById($_SESSION['user_id']);
    }
    
    return $currentUser;
}

/**
 * Check if current user owns a resource
 */
function isOwner($resourceUserId) {
    return isLoggedIn() && getCurrentUserId() == $resourceUserId;
}

/**
 * Check if current user can edit a resource (owner or admin)
 */
function canEdit($resourceUserId) {
    return isOwner($resourceUserId) || isAdmin();
}

/**
 * Check if current user can delete a resource (owner or moderator+)
 */
function canDelete($resourceUserId) {
    return isOwner($resourceUserId) || isModerator();
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token
 */
function requireCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($token)) {
        http_response_code(403);
        die('CSRF token mismatch');
    }
}

/**
 * Login user
 */
function loginUser($userId, $userData = null) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $userData['VaiTroID'] ?? ROLE_USER;
    $_SESSION['user_username'] = $userData['TenDN'] ?? '';
    $_SESSION['user_name'] = $userData['HoTen'] ?? '';
    $_SESSION['user_email'] = $userData['Email'] ?? '';
    $_SESSION['user_phone'] = $userData['SDT'] ?? '';
    $_SESSION['user_address'] = $userData['DiaChi'] ?? '';
    $_SESSION['user_role_name'] = $userData['TenVT'] ?? '';
    $_SESSION['user_avatar'] = $userData['AnhDaiDien'] ?? '';
    $_SESSION['login_time'] = time();

    // Regenerate session ID for security
    session_regenerate_id(true);

    // Log login activity
    try {
        $activity = new Tro365\Models\Activity();
        $activity->log($userId, 'login', 'Đăng nhập hệ thống');
    } catch (Exception $e) {
        // Silent fail for activity logging
    }
}

/**
 * Logout user
 */
function logoutUser() {
    $userId = getCurrentUserId();

    // Online tracking removed

    // Log logout activity
    if ($userId) {
        try {
            $activity = new Tro365\Models\Activity();
            $activity->log($userId, 'logout', 'Đăng xuất hệ thống');
        } catch (Exception $e) {
            // Silent fail for activity logging
        }
    }

    // Clear session
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Check if user account is active
 */
function isAccountActive($userData) {
    return isset($userData['TrangThai']) && $userData['TrangThai'] == USER_STATUS_ACTIVE;
}

// getRoleName() function is available in config/constants.php
