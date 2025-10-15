<?php
/**
 * System Constants - Backward Compatibility
 * Tro365 - Website thuê trọ
 *
 * DEPRECATED: These constants are kept for backward compatibility.
 * New code should use Tro365\Helpers\StatusHelper for consistent status management.
 * 
 * Status values are aliased to StatusHelper constants for consistency.
 */

// User roles
define('ROLE_USER', 1);
define('ROLE_SELLER', 2);
define('ROLE_SUPPORTER', 3);
define('ROLE_MODERATOR', 4);
define('ROLE_ADMIN', 5);

// User status - aliased to StatusHelper constants
define('USER_STATUS_ACTIVE', \Tro365\Helpers\StatusHelper::USER_ACTIVE);
define('USER_STATUS_INACTIVE', \Tro365\Helpers\StatusHelper::USER_INACTIVE);
define('USER_STATUS_BANNED', \Tro365\Helpers\StatusHelper::USER_BANNED);

// Post status - aliased to StatusHelper constants
define('POST_STATUS_PENDING', \Tro365\Helpers\StatusHelper::POST_PENDING);
define('POST_STATUS_APPROVED', \Tro365\Helpers\StatusHelper::POST_APPROVED);
define('POST_STATUS_REJECTED', \Tro365\Helpers\StatusHelper::POST_REJECTED);
define('POST_STATUS_RENTED', \Tro365\Helpers\StatusHelper::POST_RENTED);
define('POST_STATUS_HIDDEN', \Tro365\Helpers\StatusHelper::POST_HIDDEN);

// Seller registration status - aliased to StatusHelper constants
define('SELLER_STATUS_PENDING', \Tro365\Helpers\StatusHelper::SELLER_PENDING);
define('SELLER_STATUS_APPROVED', \Tro365\Helpers\StatusHelper::SELLER_APPROVED);
define('SELLER_STATUS_REJECTED', \Tro365\Helpers\StatusHelper::SELLER_REJECTED);

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
// MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT, THUMBNAIL_WIDTH, THUMBNAIL_HEIGHT removed - unused

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

// Validation rules - synchronized with config/validation.php
define('MIN_PASSWORD_LENGTH', 8);
define('MAX_PASSWORD_LENGTH', 100); // Updated to match validation.php
define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 30);
define('MAX_TITLE_LENGTH', 255);
define('MAX_DESCRIPTION_LENGTH', 2000); // Updated to match validation.php

// Search limits and rate limiting constants removed - unused in current implementation
// If needed later, implement in proper service classes

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
