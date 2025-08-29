<?php
/**
 * Logout Page
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;

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
        // Trigger logout event to stop session refresh
        if (window.sessionRefresh) {
            window.sessionRefresh.stopRefresh();
        }

        // Dispatch custom logout event
        window.dispatchEvent(new CustomEvent('userLogout'));

        // Redirect after a short delay to ensure event is processed
        setTimeout(() => {
            window.location.href = '/';
        }, 100);
    </script>
    <p>Đang đăng xuất...</p>
</body>
</html>
