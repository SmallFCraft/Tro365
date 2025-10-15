<?php
/**
 * Helper Functions
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Config;

/**
 * Escape HTML output
 */
if (!function_exists('e')) {
    function e($string)
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sanitize HTML content for safe display
 * Allows basic formatting tags but removes dangerous elements
 */
function sanitizeHtml($html)
{
    if (empty($html)) return '';

    // Allowed tags for rich text content (TinyMCE compatible)
    $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><a><div><span><table><tr><td><th><thead><tbody><tfoot><img><hr><pre><code><sub><sup><del><ins><mark>';

    // Strip dangerous tags but keep allowed ones
    $cleaned = strip_tags($html, $allowedTags);

    // Remove dangerous attributes
    $cleaned = preg_replace('/(<[^>]+) (on\w+|javascript:|vbscript:|data:)[^>]*>/i', '$1>', $cleaned);

    // Clean up links - only allow http/https
    $cleaned = preg_replace('/href=["\'](?!https?:\/\/)[^"\']*["\']/i', 'href="#"', $cleaned);

    return $cleaned;
}

/**
 * Format currency
 */
function formatCurrency($amount)
{
    // Standardize currency format site-wide: 4.000.000 ₫
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

/**
 * Format date
 */
function formatDate($date, $format = 'd/m/Y')
{
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i')
{
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Time ago function
 */
function timeAgo($datetime)
{
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'vừa xong';
    if ($time < 3600) return floor($time/60) . ' phút trước';
    if ($time < 86400) return floor($time/3600) . ' giờ trước';
    if ($time < 2592000) return floor($time/86400) . ' ngày trước';
    if ($time < 31536000) return floor($time/2592000) . ' tháng trước';

    return floor($time/31536000) . ' năm trước';
}

/**
 * Generate slug from string
 */
function generateSlug($string)
{
    $string = trim($string);
    $string = mb_strtolower($string, 'UTF-8');

    // Replace Vietnamese characters
    $vietnamese = [
        'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
        'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
        'ì', 'í', 'ị', 'ỉ', 'ĩ',
        'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
        'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
        'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
        'đ'
    ];

    $english = [
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y',
        'd'
    ];

    $string = str_replace($vietnamese, $english, $string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');

    return $string;
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...')
{
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

/**
 * Validate email - Redirected to ValidationHelper (standardized)
 */
function isValidEmail($email)
{
    $validation = \Tro365\Helpers\ValidationHelper::validateEmail($email);
    return $validation['valid'];
}

/**
 * Validate phone number - Redirected to ValidationHelper (standardized)
 */
function isValidPhone($phone)
{
    if (empty($phone)) {
        return false;
    }

    $pattern = \Tro365\Helpers\ValidationHelper::getPhonePattern();
    return preg_match($pattern, $phone);
}

/**
 * Clean input
 */
function cleanInput($input)
{
    return trim(strip_tags($input));
}

/**
 * Get file extension
 */
function getFileExtension($filename)
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Format file size
 */
function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];
}

// Authentication functions moved to includes/functions/auth.php
// This comment is kept for reference

/**
 * Set flash message
 */
function setFlashMessage($type, $message)
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Upload settings helpers (DB-config aware with safe fallbacks)
 */
function getUploadAllowedExtensions()
{
    // String like "jpg,jpeg,png"
    $val = getSystemSetting('allowed_file_types', null);
    if (empty($val)) {
        $val = UPLOAD_ALLOWED_TYPES; // fallback from app.php / .env
    }
    return strtolower(trim($val));
}

function getUploadAllowedExtensionsArray()
{
    $str = getUploadAllowedExtensions();
    $parts = array_filter(array_map('trim', explode(',', $str)));
    return array_map('strtolower', $parts);
}

function getUploadMaxSizeMB()
{
    $mb = getSystemSetting('max_upload_size', null);
    if ($mb === null || $mb === '') {
        // fallback from constant (bytes)
        return round((int)UPLOAD_MAX_SIZE / (1024 * 1024));
    }
    return (float)$mb;
}

function getUploadMaxSizeBytes()
{
    $mb = getUploadMaxSizeMB();
    // guardrails: min 1MB, max 100MB to avoid mistakes
    if ($mb < 1) { $mb = 1; }
    if ($mb > 1000) { $mb = 1000; }
    return (int)round($mb * 1024 * 1024);
}

/**
 * Get and clear flash message
 */
function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Debug function
 */
function dd($data)
{
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Enhanced Log function with categories and context
 * Now unified with LoggerHelper for consistent logging
 */
function writeLog($message, $level = 'info', $category = 'general', $context = [])
{
    try {
        // Use LoggerHelper for unified logging
        $logger = \Tro365\Helpers\LoggerHelper::getLogger($category);

        // Map level strings to Monolog levels
        switch (strtolower($level)) {
            case 'debug':
                $logger->debug($message, $context);
                break;
            case 'info':
                $logger->info($message, $context);
                break;
            case 'notice':
                $logger->notice($message, $context);
                break;
            case 'warning':
                $logger->warning($message, $context);
                break;
            case 'error':
                $logger->error($message, $context);
                break;
            case 'critical':
                $logger->critical($message, $context);
                break;
            case 'alert':
                $logger->alert($message, $context);
                break;
            case 'emergency':
                $logger->emergency($message, $context);
                break;
            default:
                $logger->info($message, $context);
                break;
        }

        // Also write to error log for critical issues (maintain backward compatibility)
        if ($level === 'error' || $level === 'critical') {
            error_log("[{$category}] {$message}");
        }

    } catch (Exception $e) {
        // Fallback to legacy logging if LoggerHelper fails
        $logDir = rtrim(LOG_PATH, '/\\');

        // Simple log files without dates
        if ($level === 'debug' || $category === 'debug') {
            $logFile = $logDir . '/debug.log';
        } else {
            $logFile = $logDir . '/app.log';
        }

        // Unified logging with full timestamp
        $timestamp = date('Y-m-d H:i:s');
        $requestInfo = '';
        if (isset($_SERVER['REQUEST_METHOD']) && isset($_SERVER['REQUEST_URI'])) {
            $requestInfo = ' | ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
        }

        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        // Always include timestamp and level
        $logMessage = "[{$timestamp}] [{$level}]{$requestInfo} {$message}{$contextStr}" . PHP_EOL;

        // Ensure directory exists and is writable
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Write log file (suppress warnings and lock file)
        @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

        // Log the fallback usage
        error_log("writeLog fallback used due to LoggerHelper error: " . $e->getMessage());
    }
}

/**
 * Specific logging functions for different categories
 */
function logDebug($message, $context = []) {
    writeLog($message, 'debug', 'debug', $context);
}

function logInfo($message, $context = []) {
    writeLog($message, 'info', 'info', $context);
}

function logWarning($message, $context = []) {
    writeLog($message, 'warning', 'warning', $context);
}

function logError($message, $context = []) {
    writeLog($message, 'error', 'error', $context);
}

function logDatabase($message, $context = []) {
    writeLog($message, 'debug', 'database', $context);
}

function logAuth($message, $context = []) {
    writeLog($message, 'info', 'auth', $context);
}

function logAPI($message, $context = []) {
    writeLog($message, 'debug', 'api', $context);
}

function logPerformance($message, $context = []) {
    writeLog($message, 'info', 'performance', $context);
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}



/**
 * Check if file is image
 */
function isImageFile($filename)
{
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    return in_array(getFileExtension($filename), $imageExtensions);
}

/**
 * Get image dimensions
 */
function getImageDimensions($imagePath)
{
    if (!file_exists($imagePath) || !isImageFile($imagePath)) {
        return false;
    }

    $imageInfo = getimagesize($imagePath);
    return $imageInfo ? ['width' => $imageInfo[0], 'height' => $imageInfo[1]] : false;
}

/**
 * Resize image
 */
function resizeImage($sourcePath, $destinationPath, $maxWidth, $maxHeight, $quality = 85)
{
    if (!file_exists($sourcePath) || !isImageFile($sourcePath)) {
        return false;
    }

    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) return false;

    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $sourceType = $imageInfo[2];

    // Calculate new dimensions
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    $newWidth = $sourceWidth * $ratio;
    $newHeight = $sourceHeight * $ratio;

    // Create image resource from source
    switch ($sourceType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }

    if (!$sourceImage) return false;

    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG and GIF
    if ($sourceType == IMAGETYPE_PNG || $sourceType == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resize
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

    // Create destination directory if it doesn't exist
    $destinationDir = dirname($destinationPath);
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }

    // Save image
    $result = false;
    switch ($sourceType) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($newImage, $destinationPath, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($newImage, $destinationPath);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($newImage, $destinationPath);
            break;
    }

    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($newImage);

    return $result;
}

/**
 * Get post image or placeholder
 */
function getPostImage($imagePath, $cssClass = '', $alt = 'Post Image')
{
    // Use new image fallback system
    return generateImageHtml($imagePath, $alt, $cssClass);
}

/**
 * Get user avatar or default avatar
 */
function getUserAvatar($avatarPath = null)
{
    return getAvatarWithFallback($avatarPath);
}

/**
 * Delete image file and all its optimized versions (WebP, AVIF, thumbnails)
 * This function handles the complete cleanup of all image variants created by the optimization system
 *
 * @param string $imagePath Relative path to the image (e.g., "assets/uploads/posts/2025/08/image.jpg")
 * @return array Results of deletion attempts with detailed status
 */
function deleteImageWithAllVersions($imagePath)
{
    $results = [
        'success' => false,
        'deleted_files' => [],
        'failed_files' => [],
        'total_deleted' => 0,
        'total_failed' => 0,
        'storage_freed' => 0
    ];

    try {
        // Convert relative path to absolute path
        $basePath = __DIR__ . '/../../';
        $fullPath = $basePath . ltrim($imagePath, '/');

        // Get file info
        $pathInfo = pathinfo($fullPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';

        // List of all possible file variants to delete
        $filesToDelete = [];

        // 1. Original file
        if (file_exists($fullPath)) {
            $filesToDelete[] = [
                'path' => $fullPath,
                'type' => 'original',
                'size' => filesize($fullPath)
            ];
        }

        // 2. WebP version
        $webpPath = $directory . '/' . $filename . '.webp';
        if (file_exists($webpPath)) {
            $filesToDelete[] = [
                'path' => $webpPath,
                'type' => 'webp',
                'size' => filesize($webpPath)
            ];
        }

        // 3. AVIF version
        $avifPath = $directory . '/' . $filename . '.avif';
        if (file_exists($avifPath)) {
            $filesToDelete[] = [
                'path' => $avifPath,
                'type' => 'avif',
                'size' => filesize($avifPath)
            ];
        }

        // 4. Thumbnail versions (multiple possible formats)
        $thumbnailPatterns = [
            'thumb_' . $filename . '.' . $extension,
            'thumb_' . $filename . '.webp',
            'thumb_' . $filename . '.avif'
        ];

        foreach ($thumbnailPatterns as $thumbPattern) {
            $thumbPath = $directory . '/' . $thumbPattern;
            if (file_exists($thumbPath)) {
                $filesToDelete[] = [
                    'path' => $thumbPath,
                    'type' => 'thumbnail',
                    'size' => filesize($thumbPath)
                ];
            }
        }

        // 5. Additional optimized versions (check for common patterns)
        $optimizedPatterns = [
            $filename . '_optimized.' . $extension,
            $filename . '_compressed.' . $extension,
            $filename . '_resized.' . $extension
        ];

        foreach ($optimizedPatterns as $optPattern) {
            $optPath = $directory . '/' . $optPattern;
            if (file_exists($optPath)) {
                $filesToDelete[] = [
                    'path' => $optPath,
                    'type' => 'optimized',
                    'size' => filesize($optPath)
                ];
            }
        }

        // Delete all found files
        foreach ($filesToDelete as $fileInfo) {
            try {
                if (unlink($fileInfo['path'])) {
                    $results['deleted_files'][] = [
                        'path' => $fileInfo['path'],
                        'type' => $fileInfo['type'],
                        'size' => $fileInfo['size']
                    ];
                    $results['total_deleted']++;
                    $results['storage_freed'] += $fileInfo['size'];
                } else {
                    $results['failed_files'][] = [
                        'path' => $fileInfo['path'],
                        'type' => $fileInfo['type'],
                        'error' => 'Failed to delete file'
                    ];
                    $results['total_failed']++;
                }
            } catch (Exception $e) {
                $results['failed_files'][] = [
                    'path' => $fileInfo['path'],
                    'type' => $fileInfo['type'],
                    'error' => $e->getMessage()
                ];
                $results['total_failed']++;
            }
        }

        // Set overall success status
        $results['success'] = ($results['total_deleted'] > 0 && $results['total_failed'] === 0);

        // Log the deletion results
        if ($results['total_deleted'] > 0) {
            writeLog("Image cleanup completed: {$results['total_deleted']} files deleted, " .
                    formatBytes($results['storage_freed']) . " storage freed for image: $imagePath",
                    'info', 'image_cleanup');
        }

        if ($results['total_failed'] > 0) {
            writeLog("Image cleanup had {$results['total_failed']} failures for image: $imagePath",
                    'warning', 'image_cleanup');
        }

    } catch (Exception $e) {
        $results['error'] = $e->getMessage();
        writeLog("Image cleanup failed for $imagePath: " . $e->getMessage(), 'error', 'image_cleanup');
    }

    return $results;
}

/**
 * Get user avatar HTML
 */
function getUserAvatarHtml($avatarPath = null, $cssClass = '', $alt = 'Avatar', $style = '')
{
    $src = getAvatarWithFallback($avatarPath);
    $styleAttr = !empty($style) ? ' style="' . e($style) . '"' : '';
    $onerror = "this.onerror=null; this.src='" . DEFAULT_AVATAR_SVG . "';";

    return '<img src="' . e($src) . '" class="' . e($cssClass) . '" alt="' . e($alt) . '" onerror="' . $onerror . '"' . $styleAttr . '>';
}

/**
 * Get website name from database or fallback to APP_NAME
 */
function getWebsiteName()
{
    static $websiteName = null;

    if ($websiteName === null) {
        try {
            $config = new Config();
            $websiteName = $config->getValue('ten_website', APP_NAME);
        } catch (Exception $e) {
            $websiteName = APP_NAME;
        }
    }

    return $websiteName;
}

/**
 * Get website description from database
 */
function getWebsiteDescription()
{
    static $websiteDescription = null;

    if ($websiteDescription === null) {
        try {
            $config = new Config();
            // Try mo_ta_website first, then meta_description, then fallback
            $websiteDescription = $config->getValue('mo_ta_website', '');
            if (empty($websiteDescription)) {
                $websiteDescription = $config->getValue('meta_description', '');
            }
            if (empty($websiteDescription)) {
                $websiteDescription = 'Website thuê trọ uy tín số 1 Việt Nam';
            }
        } catch (Exception $e) {
            $websiteDescription = 'Website thuê trọ uy tín số 1 Việt Nam';
        }
    }

    return $websiteDescription;
}

/**
 * Get meta description for SEO
 */
function getMetaDescription()
{
    static $metaDescription = null;

    if ($metaDescription === null) {
        try {
            $config = new Config();
            // Try meta_description first, then mo_ta_website, then fallback
            $metaDescription = $config->getValue('meta_description', '');
            if (empty($metaDescription)) {
                $metaDescription = $config->getValue('mo_ta_website', '');
            }
            if (empty($metaDescription)) {
                $metaDescription = 'Website thuê trọ uy tín số 1 Việt Nam';
            }
        } catch (Exception $e) {
            $metaDescription = 'Website thuê trọ uy tín số 1 Việt Nam';
        }
    }

    return $metaDescription;
}

/**
 * Get company info from database
 */
function getCompanyInfo($key, $default = '')
{
    static $companyInfo = [];

    if (!isset($companyInfo[$key])) {
        try {
            $config = new Config();
            $companyInfo[$key] = $config->getValue($key, $default);
        } catch (Exception $e) {
            $companyInfo[$key] = $default;
        }
    }

    return $companyInfo[$key];
}

/**
 * Get system settings from database
 */
function getSystemSetting($key, $default = null)
{
    static $systemSettings = [];

    if (!isset($systemSettings[$key])) {
        try {
            $config = new Config();
            $systemSettings[$key] = $config->getValue($key, $default);
        } catch (Exception $e) {
            $systemSettings[$key] = $default;
        }
    }

    return $systemSettings[$key];
}

/**
 * Get posts per page from database
 */
function getPostsPerPage()
{
    return (int)getSystemSetting('so_bai_dang_moi_trang', POSTS_PER_PAGE);
}

/**
 * Get commission rate from database
 */
function getCommissionRate()
{
    return (float)getSystemSetting('ty_le_hoa_hong', COMMISSION_RATE);
}

/**
 * Get post validity period from database
 */
function getPostValidityDays()
{
    return (int)getSystemSetting('thoi_gian_hieu_luc_bai_dang', 30);
}

/**
 * Get max upload size from database (in MB)
 * @deprecated Use getUploadMaxSizeMB() instead for consistency
 */
function getMaxUploadSize()
{
    // Redirect to the unified function
    return (int)getUploadMaxSizeMB();
}

/**
 * Get allowed file types from database
 * @deprecated Use getUploadAllowedExtensions() instead for consistency
 */
function getAllowedFileTypes()
{
    // Redirect to the unified function
    return getUploadAllowedExtensions();
}

/**
 * Check if registration is enabled
 */
function isRegistrationEnabled()
{
    return (bool)getSystemSetting('enable_registration', true);
}

/**
 * Check if seller registration is enabled
 */
function isSellerRegistrationEnabled()
{
    return (bool)getSystemSetting('enable_seller_registration', true);
}

/**
 * Check if email verification is required
 */
function isEmailVerificationRequired()
{
    return (bool)getSystemSetting('require_email_verification', false);
}

/**
 * Check if maintenance mode is enabled
 */
function isMaintenanceModeEnabled()
{
    return (bool)getSystemSetting('enable_maintenance_mode', false);
}

/**
 * Check if debug mode is enabled
 */
function isDebugModeEnabled()
{
    try {
        // Check database setting first, fallback to APP_DEBUG constant
        $dbSetting = getSystemSetting('app_debug', null);
        if ($dbSetting !== null) {
            return (bool)$dbSetting;
        }
    } catch (Exception $e) {
        // If database error, fallback to constant
    }

    // Fallback to APP_DEBUG constant if database setting doesn't exist
    return defined('APP_DEBUG') ? APP_DEBUG : false;
}

/**
 * Get application version
 */
function getAppVersion()
{
    // Don't use cache for version to ensure real-time updates
    try {
        $config = new \Tro365\Core\Config();
        return $config->getValue('app_version', '1.0.0');
    } catch (Exception $e) {
        return '1.0.0';
    }
}

/**
 * Set application version
 */
function setAppVersion($version, $customDescription = null)
{
    try {
        $config = new \Tro365\Core\Config();

        // Get current version before updating
        $currentVersion = getAppVersion();

        // Check if version is the same
        if ($currentVersion === $version) {
            // If same version but has custom description, update history only
            if (!empty($customDescription)) {
                return updateVersionDescription($version, $customDescription);
            }
            // Same version, no custom description - return success without doing anything
            return true;
        }

        // Update version
        $result = $config->setValue('app_version', $version);

        // Always add to version history for different versions
        if ($result) {
            addVersionHistory($version, $currentVersion, $customDescription);
        }

        return $result;
    } catch (Exception $e) {
        writeLog("Error setting app version: " . $e->getMessage());
        return false;
    }
}

/**
 * Add version to history
 */
function addVersionHistory($newVersion, $previousVersion = null, $customDescription = null)
{
    try {
        $config = new \Tro365\Core\Config();

        // Get existing history
        $historyJson = $config->getValue('version_history', '[]');
        $history = json_decode($historyJson, true) ?: [];

        // Use custom description if provided, otherwise generate automatically
        $description = !empty($customDescription)
            ? $customDescription
            : generateVersionDescription($newVersion, $previousVersion);

        // Add new version entry
        $newEntry = [
            'version' => $newVersion,
            'date' => date('Y-m-d H:i:s'),
            'previous_version' => $previousVersion,
            'description' => $description,
            'is_custom_description' => !empty($customDescription)
        ];

        // Add to beginning of array (newest first)
        array_unshift($history, $newEntry);

        // Keep only last 20 versions
        $history = array_slice($history, 0, 20);

        // Save back to config
        return $config->setValue('version_history', json_encode($history));

    } catch (Exception $e) {
        writeLog("Error adding version history: " . $e->getMessage());
        return false;
    }
}

/**
 * Get version history
 */
function getVersionHistory()
{
    try {
        $config = new \Tro365\Core\Config();
        $historyJson = $config->getValue('version_history', '[]');
        $history = json_decode($historyJson, true) ?: [];

        // If history is empty, create initial entries
        if (empty($history)) {
            $currentVersion = getAppVersion();
            $initialHistory = [
                [
                    'version' => $currentVersion,
                    'date' => date('Y-m-d H:i:s'),
                    'previous_version' => null,
                    'description' => 'Phiên bản hiện tại'
                ],
                [
                    'version' => '1.0.0',
                    'date' => '2025-01-01 00:00:00',
                    'previous_version' => null,
                    'description' => 'Phiên bản khởi tạo'
                ]
            ];

            // Save initial history
            $config->setValue('version_history', json_encode($initialHistory));
            return $initialHistory;
        }

        return $history;

    } catch (Exception $e) {
        writeLog("Error getting version history: " . $e->getMessage());
        return [];
    }
}

/**
 * Update description for current version
 */
function updateVersionDescription($version, $newDescription)
{
    try {
        $config = new \Tro365\Core\Config();

        // Get existing history
        $historyJson = $config->getValue('version_history', '[]');
        $history = json_decode($historyJson, true) ?: [];

        // Find and update the current version entry
        $updated = false;
        foreach ($history as &$entry) {
            if ($entry['version'] === $version) {
                $entry['description'] = $newDescription;
                $entry['is_custom_description'] = true;
                $entry['date'] = date('Y-m-d H:i:s'); // Update timestamp
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            // If version not found in history, add it
            $newEntry = [
                'version' => $version,
                'date' => date('Y-m-d H:i:s'),
                'previous_version' => null,
                'description' => $newDescription,
                'is_custom_description' => true
            ];
            array_unshift($history, $newEntry);
        }

        // Save updated history
        return $config->setValue('version_history', json_encode($history));

    } catch (Exception $e) {
        writeLog("Error updating version description: " . $e->getMessage());
        return false;
    }
}

/**
 * Update description for any version in history
 */
function updateAnyVersionDescription($version, $newDescription)
{
    try {
        $config = new \Tro365\Core\Config();

        // Get existing history
        $historyJson = $config->getValue('version_history', '[]');
        $history = json_decode($historyJson, true) ?: [];

        // Find and update the specified version entry
        $updated = false;
        foreach ($history as &$entry) {
            if ($entry['version'] === $version) {
                $entry['description'] = $newDescription;
                $entry['is_custom_description'] = true;
                // Keep original date, don't update timestamp for old versions
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            return false; // Version not found
        }

        // Save updated history
        return $config->setValue('version_history', json_encode($history));

    } catch (Exception $e) {
        writeLog("Error updating any version description: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate version description
 */
function generateVersionDescription($newVersion, $previousVersion = null)
{
    if (!$previousVersion) {
        return 'Phiên bản khởi tạo';
    }

    $newParts = explode('.', $newVersion);
    $oldParts = explode('.', $previousVersion);

    if (count($newParts) !== 3 || count($oldParts) !== 3) {
        return 'Cập nhật phiên bản';
    }

    $newMajor = (int)$newParts[0];
    $newMinor = (int)$newParts[1];
    $newPatch = (int)$newParts[2];

    $oldMajor = (int)$oldParts[0];
    $oldMinor = (int)$oldParts[1];
    $oldPatch = (int)$oldParts[2];

    if ($newMajor > $oldMajor) {
        return 'Cập nhật lớn (Major Update)';
    } elseif ($newMinor > $oldMinor) {
        return 'Cập nhật tính năng (Minor Update)';
    } elseif ($newPatch > $oldPatch) {
        return 'Sửa lỗi (Patch Update)';
    } else {
        return 'Cập nhật phiên bản';
    }
}

/**
 * Get email settings from database
 */
function getEmailSetting($key, $default = '')
{
    static $emailSettings = [];

    if (!isset($emailSettings[$key])) {
        try {
            $config = new Config();
            $emailSettings[$key] = $config->getValue($key, $default);
        } catch (Exception $e) {
            $emailSettings[$key] = $default;
        }
    }

    return $emailSettings[$key];
}

/**
 * Get email configuration array
 */
function getEmailConfig()
{
    // Get email settings from database (admin settings)
    $fromAddress = getEmailSetting('mail_from_address', '');
    $fromName = getEmailSetting('mail_from_name', '');

    // Ensure fallback values if empty - use configured values from database
    if (empty($fromAddress)) {
        // Try to get from company info first, then fallback to domain-based email
        $fromAddress = getCompanyInfo('email_admin', 'noreply@tro.loading99.site');
    }
    if (empty($fromName)) {
        $fromName = getWebsiteName();
    }

    return [
        'driver' => getEmailSetting('mail_driver', 'smtp'),
        'host' => getEmailSetting('mail_host', 'smtp.gmail.com'),
        'port' => (int)getEmailSetting('mail_port', 587),
        'encryption' => getEmailSetting('mail_encryption', 'tls'),
        'username' => getEmailSetting('mail_username', ''),
        'password' => getEmailSetting('mail_password', ''),
        'from_address' => $fromAddress,
        'from_name' => $fromName,
    ];
}

/**
 * Get EmailService instance
 */
function getEmailService()
{
    static $emailService = null;

    if ($emailService === null) {
        require_once __DIR__ . '/../../classes/services/EmailService.php';
        $emailService = new \Tro365\Services\EmailService();
    }

    return $emailService;
}

/**
 * Send email using EmailService (helper function)
 */
function sendEmail($to, $subject, $body, $isHtml = true, $attachments = [])
{
    try {
        $emailService = getEmailService();
        return $emailService->send($to, $subject, $body, $isHtml, $attachments);
    } catch (Exception $e) {
        writeLog("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send welcome email (helper function)
 */
function sendWelcomeEmail($userEmail, $userName)
{
    try {
        $emailService = getEmailService();
        return $emailService->sendWelcomeEmail($userEmail, $userName);
    } catch (Exception $e) {
        writeLog("Welcome email failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send contact email (helper function)
 */
function sendContactEmail($name, $email, $subject, $message)
{
    try {
        $emailService = getEmailService();
        return $emailService->sendContactEmail($name, $email, $subject, $message);
    } catch (Exception $e) {
        writeLog("Contact email failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send password reset email (helper function)
 */
function sendPasswordResetEmail($userEmail, $resetLink, $token)
{
    try {
        $emailService = getEmailService();
        return $emailService->sendPasswordResetEmail($userEmail, $resetLink, $token);
    } catch (Exception $e) {
        writeLog("Password reset email failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email verification email (helper function)
 */
function sendEmailVerification($userEmail, $userName, $verificationLink, $token)
{
    try {
        $emailService = getEmailService();
        return $emailService->sendEmailVerification($userEmail, $userName, $verificationLink, $token);
    } catch (Exception $e) {
        writeLog("Email verification failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification email (helper function)
 */
function sendNotificationEmail($to, $subject, $message, $type = 'info')
{
    try {
        $emailService = getEmailService();
        return $emailService->sendNotificationEmail($to, $subject, $message, $type);
    } catch (Exception $e) {
        writeLog("Notification email failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Test SMTP connection (helper function)
 */
function testSmtpConnection($config = null)
{
    try {
        $emailService = getEmailService();
        return $emailService->testConnection($config);
    } catch (Exception $e) {
        writeLog("SMTP connection test failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Lỗi test kết nối: ' . $e->getMessage()
        ];
    }
}

/**
 * Get SEO settings from database
 */
function getSEOSetting($key, $default = '')
{
    static $seoSettings = [];

    if (!isset($seoSettings[$key])) {
        try {
            $config = new Config();
            $seoSettings[$key] = $config->getValue($key, $default);
        } catch (Exception $e) {
            $seoSettings[$key] = $default;
        }
    }

    return $seoSettings[$key];
}

/**
 * Get meta keywords
 */
function getMetaKeywords()
{
    return getSEOSetting('meta_keywords', 'thuê trọ, phòng trọ, nhà trọ, tìm trọ');
}

/**
 * Get Google Analytics ID
 */
function getGoogleAnalyticsId()
{
    return getSEOSetting('google_analytics_id', '');
}

/**
 * Get Facebook Pixel ID
 */
function getFacebookPixelId()
{
    return getSEOSetting('facebook_pixel_id', '');
}

/**
 * Get Google Search Console verification code
 */
function getGoogleSearchConsole()
{
    return getSEOSetting('google_search_console', '');
}

/**
 * Check if sitemap is enabled
 */
function isSitemapEnabled()
{
    return (bool)getSEOSetting('enable_sitemap', true);
}

/**
 * Check if robots.txt is enabled
 */
function isRobotsTxtEnabled()
{
    return (bool)getSEOSetting('enable_robots_txt', true);
}

/**
 * Get image with fallback to default no-image
 * @param string|null $imagePath The image path to check
 * @param string|null $fallback Custom fallback image (optional)
 * @return string The image path or fallback
 */
function getImageWithFallback($imagePath, $fallback = null)
{
    // If no image path provided, return default
    if (empty($imagePath)) {
        return $fallback ?? DEFAULT_NO_IMAGE;
    }

    // If image path starts with http/https, return as is (external image)
    if (preg_match('/^https?:\/\//', $imagePath)) {
        return $imagePath;
    }

    // If image path doesn't start with /, add it
    if (!str_starts_with($imagePath, '/')) {
        $imagePath = '/' . $imagePath;
    }

    // Check if file exists on server
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return $imagePath;
    }

    // Return fallback if file doesn't exist
    return $fallback ?? DEFAULT_NO_IMAGE;
}

/**
 * Get avatar with fallback
 * @param string|null $avatarPath The avatar path to check
 * @return string The avatar path or default avatar
 */
function getAvatarWithFallback($avatarPath)
{
    return getImageWithFallback($avatarPath, DEFAULT_AVATAR_SVG);
}

/**
 * Generate image HTML with automatic fallback
 * @param string|null $imagePath The image path
 * @param string $alt Alt text for image
 * @param string $class CSS classes
 * @param array $attributes Additional HTML attributes
 * @return string HTML img tag with onerror fallback
 */
function generateImageHtml($imagePath, $alt = '', $class = '', $attributes = [])
{
    $src = getImageWithFallback($imagePath);
    $alt = e($alt);
    $class = e($class);

    // Build attributes string
    $attrString = '';
    foreach ($attributes as $key => $value) {
        $attrString .= ' ' . e($key) . '="' . e($value) . '"';
    }

    // Add onerror fallback for client-side handling
    $onerror = "this.onerror=null; this.src='" . DEFAULT_NO_IMAGE . "';";

    return sprintf(
        '<img src="%s" alt="%s" class="%s" onerror="%s"%s>',
        e($src),
        $alt,
        $class,
        $onerror,
        $attrString
    );
}

/**
 * Unified Image Processing Functions
 * These functions provide a unified interface to both native PHP and ImageHelper
 */

/**
 * Get image dimensions (unified interface)
 * @param string $imagePath Path to image file
 * @param bool $useAdvanced Whether to use ImageHelper (Intervention Image) or native PHP
 * @return array|false Array with width/height or false on failure
 */
function getUnifiedImageDimensions($imagePath, $useAdvanced = false)
{
    if ($useAdvanced && class_exists('\Tro365\Helpers\ImageHelper')) {
        try {
            return \Tro365\Helpers\ImageHelper::getDimensions($imagePath);
        } catch (Exception $e) {
            // Fallback to native function
            return getImageDimensions($imagePath);
        }
    }

    return getImageDimensions($imagePath);
}

/**
 * Resize image (unified interface)
 * @param string $sourcePath Source image path
 * @param string $destinationPath Destination path
 * @param int $maxWidth Maximum width
 * @param int $maxHeight Maximum height
 * @param int $quality Image quality (1-100)
 * @param bool $useAdvanced Whether to use ImageHelper or native PHP
 * @return bool|string Success status or path
 */
function resizeImageUnified($sourcePath, $destinationPath, $maxWidth, $maxHeight, $quality = 85, $useAdvanced = false)
{
    if ($useAdvanced && class_exists('\Tro365\Helpers\ImageHelper')) {
        try {
            return \Tro365\Helpers\ImageHelper::resize($sourcePath, $maxWidth, $maxHeight, $destinationPath);
        } catch (Exception $e) {
            // Fallback to native function
            return resizeImage($sourcePath, $destinationPath, $maxWidth, $maxHeight, $quality);
        }
    }

    return resizeImage($sourcePath, $destinationPath, $maxWidth, $maxHeight, $quality);
}

/**
 * Create thumbnail (unified interface)
 * @param string $imagePath Source image path
 * @param string|null $thumbnailPath Thumbnail destination path
 * @param int $width Thumbnail width
 * @param int $height Thumbnail height
 * @param bool $useAdvanced Whether to use ImageHelper or native PHP
 * @return string|false Thumbnail path or false on failure
 */
function createThumbnailUnified($imagePath, $thumbnailPath = null, $width = 300, $height = 200, $useAdvanced = false)
{
    if ($useAdvanced && class_exists('\Tro365\Helpers\ImageHelper')) {
        try {
            return \Tro365\Helpers\ImageHelper::createThumbnail($imagePath, $thumbnailPath, $width, $height);
        } catch (Exception $e) {
            // Fallback to basic resize
            if (!$thumbnailPath) {
                $pathInfo = pathinfo($imagePath);
                $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
            }
            return resizeImage($imagePath, $thumbnailPath, $width, $height);
        }
    }

    // Use basic resize for thumbnail creation
    if (!$thumbnailPath) {
        $pathInfo = pathinfo($imagePath);
        $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
    }
    return resizeImage($imagePath, $thumbnailPath, $width, $height);
}



// Automatic, lightweight cache pruning (no cron/task scheduler needed)
if (!function_exists('sys_auto_prune_cache')) {
    /**
     * Prune var/cache files older than retention window with sampling and time budget.
     * Also performs a one-time cleanup of legacy '/cache' directory if present.
     * This runs opportunistically during normal requests and is kept very cheap.
     */
    function sys_auto_prune_cache(array $opts = [])
    {
        try {
            // Sampling to avoid running every request - Reduced frequency for better performance
            $samplePercent = $opts['samplePercent'] ?? 0.5; // 0.5% of requests (was 2%)
            if (random_int(1, 100) > max(1, min(100, (int)$samplePercent))) return;

            // Do not run too frequently - Extended cooldown to reduce performance impact
            $cooldown = (int)($opts['cooldownSeconds'] ?? 1800); // 30 minutes (was 5 minutes)
            $now = time();
            $next = cache_get('sys:cache_prune_next', 0, $cooldown);
            if ($now < (int)$next) return;
            cache_set('sys:cache_prune_next', $now + $cooldown, $cooldown);

            $root = realpath(__DIR__ . '/../../');
            if (!$root) return;
            $varCache = $root . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache';
            $legacyCache = $root . DIRECTORY_SEPARATOR . 'cache';

            // One-time legacy cleanup
            if (is_dir($legacyCache) && !cache_get('sys:legacy_cache_cleaned', false, 31536000)) {
                try {
                    $it = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($legacyCache, FilesystemIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($it as $fi) {
                        if ($fi->isFile()) @unlink($fi->getPathname()); else @rmdir($fi->getPathname());
                    }
                    @rmdir($legacyCache);
                } catch (\Throwable $e) { /* ignore */ }
                cache_set('sys:legacy_cache_cleaned', true, 31536000); // 1 year
            }

            // Prune var/cache with time budget
            if (!is_dir($varCache)) return;
            $retention = (int)($opts['retentionSeconds'] ?? 86400); // 24h
            $timeBudgetMs = (int)($opts['timeBudgetMs'] ?? 40); // ~40ms budget
            $start = microtime(true);
            $maxFiles = (int)($opts['maxFilesPerRun'] ?? 200);
            $deleted = 0;

            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($varCache, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $fi) {
                if ($fi->isFile()) {
                    if (($now - $fi->getMTime()) > $retention) {
                        @unlink($fi->getPathname());
                        $deleted++;
                    }
                } else if ($fi->isDir()) {
                    @rmdir($fi->getPathname()); // remove empty dirs
                }
                // Stop if out of budget
                if ($deleted >= $maxFiles) break;
                if (((microtime(true) - $start) * 1000) > $timeBudgetMs) break;
            }
        } catch (\Throwable $e) {
            // Never throw in request path
        }
    }
}

// Cache helpers (PSR-16 via Symfony Cache), fallback to file-based
if (!function_exists('cache_is_enabled')) {
    function cache_is_enabled() { return defined('CACHE_ENABLED') ? CACHE_ENABLED : false; }
}

if (!function_exists('cache_client')) {
    function cache_client() {
        static $client = null;
        if ($client !== null) return $client;
        if (!cache_is_enabled()) return $client = null;
        try {
            // Prefer Symfony FilesystemAdapter in var/cache
            $dir = __DIR__ . '/../../var/cache';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $pool = new \Symfony\Component\Cache\Adapter\FilesystemAdapter('tro365', 0, $dir);
            $client = new \Symfony\Component\Cache\Psr16Cache($pool);
            return $client;
        } catch (\Throwable $e) {
            return $client = null;
        }
    }
}

if (!function_exists('cache_get')) {
    function cache_get($key, $default = null, $ttlSeconds = null)
    {
        if (!cache_is_enabled()) return $default;
        $c = cache_client();
        if ($c) {
            try {
                // PSR-16 get with default + instrumentation
                $has = $c->has($key);
                if ($has) {
                    $val = $c->get($key);
                    try { \Tro365\Services\PerformanceOptimizationService::getInstance()->trackCacheHit((string)$key); } catch (\Throwable $e2) {}
                    return $val;
                }
                try { \Tro365\Services\PerformanceOptimizationService::getInstance()->trackCacheMiss((string)$key); } catch (\Throwable $e3) {}
                return $default;
            } catch (\Throwable $e) { /* fallback */ }
        }
        // Fallback minimal file cache + instrumentation
        $file = __DIR__ . '/../../var/cache/' . sha1((string)$key) . '.cache.json';
        if (!is_file($file)) { try { \Tro365\Services\PerformanceOptimizationService::getInstance()->trackCacheMiss((string)$key); } catch (\Throwable $e4) {} return $default; }
        $ttl = $ttlSeconds ?? (defined('CACHE_LIFETIME') ? CACHE_LIFETIME : 3600);
        $meta = @json_decode(@file_get_contents($file), true);
        if (!is_array($meta) || !array_key_exists('value', $meta) || !isset($meta['time'])) { try { \Tro365\Services\PerformanceOptimizationService::getInstance()->trackCacheMiss((string)$key); } catch (\Throwable $e5) {} return $default; }
        if ((time() - (int)$meta['time']) > (int)$ttl) { @unlink($file); try { \Tro365\Services\PerformanceOptimizationService::getInstance()->trackCacheMiss((string)$key); } catch (\Throwable $e6) {} return $default; }
        try { \Tro365\Services\PerformanceOptimizationService::getInstance()->trackCacheHit((string)$key); } catch (\Throwable $e7) {}
        return $meta['value'];
    }
}

if (!function_exists('cache_set')) {
    function cache_set($key, $value, $ttlSeconds = null)
    {
        if (!cache_is_enabled()) return false;
        $c = cache_client();
        if ($c) {
            try {
                $ok = $c->set($key, $value, $ttlSeconds ?? (defined('CACHE_LIFETIME') ? CACHE_LIFETIME : 3600));
                if ($ok) { try { \Tro365\Services\PerformanceOptimizationService::getInstance()->trackCacheSet((string)$key); } catch (\Throwable $e2) {} }
                return $ok;
            } catch (\Throwable $e) { /* fallback */ }
        }
        // Fallback minimal file cache
        $dir = __DIR__ . '/../../var/cache';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $file = $dir . '/' . sha1((string)$key) . '.cache.json';
        $payload = json_encode(['time' => time(), 'value' => $value]);
        $ok = (bool)@file_put_contents($file, $payload, LOCK_EX);
        if ($ok) { try { \Tro365\Services\PerformanceOptimizationService::getInstance()->trackCacheSet((string)$key); } catch (\Throwable $e3) {} }
        return $ok;
    }
}

if (!function_exists('cache_delete')) {
    function cache_delete($key)
    {
        $c = cache_client();
        if ($c) { try { $c->delete($key); } catch (\Throwable $e) {} }
        $file = __DIR__ . '/../../var/cache/' . sha1((string)$key) . '.cache.json';
        if (is_file($file)) @unlink($file);
    }
}

/**
 * ========================================
 * DATA CONSISTENCY HELPER FUNCTIONS
 * ========================================
 * These functions ensure consistent data display across the system
 * by handling the relationship between KhachHang and DangKySeller tables
 */

/**
 * Get shared DataConsistency instance (eliminates duplication)
 */
function getDataConsistencyInstance() {
    static $dataConsistency = null;

    if ($dataConsistency === null) {
        $dataConsistency = new \Tro365\Services\DataConsistencyService();
    }

    return $dataConsistency;
}

/**
 * ========================================
 * UPLOAD CONFIGURATION HELPER FUNCTIONS
 * ========================================
 * These functions provide consistent upload configuration across the system
 */

/**
 * Get upload allowed extensions as array
 */
if (!function_exists('getUploadAllowedExtensionsArray')) {
    function getUploadAllowedExtensionsArray() {
        $types = UPLOAD_ALLOWED_TYPES;
        return explode(',', str_replace(' ', '', $types));
    }
}

/**
 * Get upload max size in bytes
 */
if (!function_exists('getUploadMaxSizeBytes')) {
    function getUploadMaxSizeBytes() {
        return UPLOAD_MAX_SIZE;
    }
}

/**
 * Get effective seller information (merged with user data)
 * This ensures consistent data display across the system
 */
function getEffectiveSellerInfo($sellerId) {
    return getDataConsistencyInstance()->getCompleteSellerInfo($sellerId);
}

/**
 * Get seller by user ID with consistent data
 */
function getSellerByUserId($userId) {
    return getDataConsistencyInstance()->getSellerByUserId($userId);
}

/**
 * Check if seller has business-specific information
 */
function sellerHasBusinessInfo($sellerId) {
    return getDataConsistencyInstance()->hasBusinessSpecificInfo($sellerId);
}

/**
 * Get effective value for seller data (seller-specific or fallback to user data)
 */
function getEffectiveSellerValue($sellerValue, $userValue) {
    return getDataConsistencyInstance()->getEffectiveValue($sellerValue, $userValue);
}

/**
 * Get optimized image URL with WebP support and responsive sizing
 * @param string $imagePath Original image path
 * @param int $width Target width (optional)
 * @param int $height Target height (optional)
 * @return string Optimized image URL
 */
function getOptimizedImageUrl($imagePath, $width = null, $height = null) {
    // Check if browser supports WebP
    $supportsWebP = false;
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        $supportsWebP = strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    }

    // Get file info
    $pathInfo = pathinfo($imagePath);
    $directory = $pathInfo['dirname'];
    $filename = $pathInfo['filename'];
    $extension = $pathInfo['extension'] ?? 'jpg';

    // Generate WebP path
    $webpPath = $directory . '/' . $filename . '.webp';
    $webpFullPath = $_SERVER['DOCUMENT_ROOT'] . $webpPath;

    // If WebP exists and browser supports it, use WebP
    if ($supportsWebP && file_exists($webpFullPath)) {
        $optimizedPath = $webpPath;
    } else {
        $optimizedPath = $imagePath;
    }

    // Add responsive sizing parameters if specified
    if ($width || $height) {
        $params = [];
        if ($width) $params['w'] = $width;
        if ($height) $params['h'] = $height;

        if (!empty($params)) {
            $optimizedPath .= '?' . http_build_query($params);
        }
    }

    return $optimizedPath;
}

/**
 * Generate responsive image srcset for different screen sizes
 * @param string $imagePath Base image path
 * @param array $sizes Array of widths for srcset
 * @return string srcset attribute value
 */
function generateResponsiveSrcset($imagePath, $sizes = [400, 600, 800, 1200]) {
    $srcset = [];
    foreach ($sizes as $size) {
        $srcset[] = getOptimizedImageUrl($imagePath, $size) . ' ' . $size . 'w';
    }
    return implode(', ', $srcset);
}
