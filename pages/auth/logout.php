<?php
/**
 * Logout Page
 * Tro365 - Website thuê trọ
 */

// Performance optimization includes
require_once __DIR__ . '/../../includes/performance/optimization.php';

use Tro365\Core\Auth;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();

// Logout user
$auth->logout();

// Set flash message
setFlashMessage(MSG_SUCCESS, 'Đăng xuất thành công!');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Đăng xuất...</title>
</head>
<body>
    <script>
        // Clear all global user variables to prevent session refresh re-initialization
        window.currentUserRole = null;
        window.currentUserStatus = null;
        window.TRO365_USER_ID = undefined;

        // Update Tro365Config if it exists
        if (window.Tro365Config) {
            window.Tro365Config.userRole = null;
            window.Tro365Config.isLoggedIn = false;
        }

        // Trigger logout event to stop session refresh
        if (window.sessionRefresh) {
            window.sessionRefresh.stopRefresh();
            window.sessionRefresh.destroy();
            window.sessionRefresh = null;
        }

        // Dispatch custom logout event
        window.dispatchEvent(new CustomEvent('userLogout'));

        // Clear any remaining intervals that might be running
        for (let i = 1; i < 99999; i++) {
            clearInterval(i);
        }

        // Redirect after a short delay to ensure event is processed
        setTimeout(() => {
            window.location.href = '/';
        }, 100);
    </script>
    <p>Đang đăng xuất...</p>

    </body>
</html>
