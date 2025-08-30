<?php
/**
 * System Constants
 * Tro365 - Website thuê trọ
 *
 * Note: Status constants are kept for backward compatibility.
 * New code should use Tro365\Helpers\StatusHelper for consistent status management.
 */

// User roles
define('ROLE_USER', 1);
define('ROLE_SELLER', 2);
define('ROLE_SUPPORTER', 3);
define('ROLE_MODERATOR', 4);
define('ROLE_ADMIN', 5);

// User status
define('USER_STATUS_ACTIVE', 1);
define('USER_STATUS_INACTIVE', 0);
define('USER_STATUS_BANNED', 2);

// Post status
define('POST_STATUS_PENDING', 0);
define('POST_STATUS_APPROVED', 1);
define('POST_STATUS_REJECTED', 2);
define('POST_STATUS_RENTED', 3);
define('POST_STATUS_HIDDEN', 4);

// Seller registration status
define('SELLER_STATUS_PENDING', 0);
define('SELLER_STATUS_APPROVED', 1);
define('SELLER_STATUS_REJECTED', 2);

// Contact status
define('CONTACT_STATUS_NEW', 0);
define('CONTACT_STATUS_VIEWED', 1);
define('CONTACT_STATUS_CONTACTED', 2);
define('CONTACT_STATUS_RENTED', 3);
define('CONTACT_STATUS_CANCELLED', 4);

// Transaction status
define('TRANSACTION_STATUS_PENDING', 0);
define('TRANSACTION_STATUS_CONFIRMED', 1);
define('TRANSACTION_STATUS_PAID', 2);
define('TRANSACTION_STATUS_COMPLETED', 3);
define('TRANSACTION_STATUS_CANCELLED', 4);

// Commission status
define('COMMISSION_STATUS_UNPAID', 0);
define('COMMISSION_STATUS_PAID', 1);

// Note: Report, Notification, and Image type constants removed as they were unused

// File upload limits (use UPLOAD_MAX_SIZE from app.php)
define('MAX_IMAGE_WIDTH', 1920);
define('MAX_IMAGE_HEIGHT', 1080);
define('THUMBNAIL_WIDTH', 300);
define('THUMBNAIL_HEIGHT', 200);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Note: Cache key constants removed as they were unused

// Date formats
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('TIME_FORMAT', 'H:i');

// Default values (moved to bottom of file)
define('DEFAULT_POST_IMAGE', 'assets/images/default/post.jpg');

// Validation rules
define('MIN_PASSWORD_LENGTH', 8);
define('MAX_PASSWORD_LENGTH', 50);
define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 30);
define('MAX_TITLE_LENGTH', 255);
define('MAX_DESCRIPTION_LENGTH', 1000);

// Search limits
define('MIN_SEARCH_LENGTH', 2);
define('MAX_SEARCH_RESULTS', 100);

// Rate limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Note: Email template constants removed as they were unused

// System messages
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'error');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');

// Note: API response code constants removed as they were unused

// Role names mapping
$ROLE_NAMES = [
    ROLE_USER => 'Người dùng',
    ROLE_SELLER => 'Người cho thuê',
    ROLE_SUPPORTER => 'Nhân viên hỗ trợ',
    ROLE_MODERATOR => 'Kiểm duyệt viên',
    ROLE_ADMIN => 'Quản trị viên'
];

// Status names mapping
$STATUS_NAMES = [
    'user' => [
        USER_STATUS_ACTIVE => 'Hoạt động',
        USER_STATUS_INACTIVE => 'Không hoạt động',
        USER_STATUS_BANNED => 'Bị cấm'
    ],
    'post' => [
        POST_STATUS_PENDING => 'Chờ duyệt',
        POST_STATUS_APPROVED => 'Đã duyệt',
        POST_STATUS_REJECTED => 'Từ chối',
        POST_STATUS_RENTED => 'Đã thuê',
        POST_STATUS_HIDDEN => 'Ẩn'
    ],
    'seller' => [
        SELLER_STATUS_PENDING => 'Chờ duyệt',
        SELLER_STATUS_APPROVED => 'Đã duyệt',
        SELLER_STATUS_REJECTED => 'Từ chối'
    ]
];

// Default images
define('DEFAULT_NO_IMAGE', '/assets/images/default/no-image.png');
define('DEFAULT_AVATAR_SVG', '/assets/images/default/avatar.svg');

// Helper functions for constants
function getRoleName($roleId)
{
    global $ROLE_NAMES;
    return $ROLE_NAMES[$roleId] ?? 'Không xác định';
}

function getStatusName($type, $statusId)
{
    global $STATUS_NAMES;
    return $STATUS_NAMES[$type][$statusId] ?? 'Không xác định';
}
