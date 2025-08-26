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

// Redirect to home
redirect('/');
?>
