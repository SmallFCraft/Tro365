<?php
/**
 * Admin Cache Clear API
 * Tro365 - Website thuê trọ
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/functions/auth.php';

use Tro365\Core\Auth;

// Set JSON response header
header('Content-Type: application/json');

try {
    // Check admin authentication
    $auth = new Auth();
    if (!$auth->isLoggedIn() || !$auth->hasRole(ROLE_ADMIN)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Không có quyền truy cập'
        ]);
        exit;
    }

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        exit;
    }

    // Clear different types of cache
    $cacheCleared = [];
    $errors = [];

    // 1. Clear PHP OPcache if available
    if (function_exists('opcache_reset')) {
        if (opcache_reset()) {
            $cacheCleared[] = 'OPcache';
        } else {
            $errors[] = 'Không thể xóa OPcache';
        }
    }

    // 2. Clear session-based cache
    if (isset($_SESSION['cache'])) {
        unset($_SESSION['cache']);
        $cacheCleared[] = 'Session cache';
    }

    // 3. Clear file-based cache (var/cache directory)
    $varCacheDir = __DIR__ . '/../../var/cache';
    if (is_dir($varCacheDir)) {
        $deletedFiles = 0;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($varCacheDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $fileinfo) {
            try {
                if ($fileinfo->isFile()) {
                    if (unlink($fileinfo->getPathname())) {
                        $deletedFiles++;
                    } else {
                        $errors[] = 'Không thể xóa file: ' . basename($fileinfo->getPathname());
                    }
                } elseif ($fileinfo->isDir()) {
                    @rmdir($fileinfo->getPathname()); // Remove empty directories
                }
            } catch (Throwable $e) {
                $errors[] = 'Lỗi xóa cache: ' . $e->getMessage();
            }
        }

        if ($deletedFiles > 0) {
            $cacheCleared[] = "File cache ({$deletedFiles} files)";
        }
    }

    // 3b. Clear legacy cache directory (if exists)
    $legacyCacheDir = __DIR__ . '/../../cache';
    if (is_dir($legacyCacheDir)) {
        $files = glob($legacyCacheDir . '/*');
        $deletedLegacyFiles = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $deletedLegacyFiles++;
                } else {
                    $errors[] = 'Không thể xóa legacy file: ' . basename($file);
                }
            }
        }
        if ($deletedLegacyFiles > 0) {
            $cacheCleared[] = "Legacy cache ({$deletedLegacyFiles} files)";
        }
    }

    // 4. Clear log files (logs directory)
    $logsDir = __DIR__ . '/../../logs';
    if (is_dir($logsDir)) {
        $deletedLogFiles = 0;
        $logFiles = glob($logsDir . '/*.log');

        foreach ($logFiles as $logFile) {
            if (is_file($logFile)) {
                // Keep recent log files (today's logs), delete older ones
                $fileDate = date('Y-m-d', filemtime($logFile));
                $today = date('Y-m-d');

                // Delete log files older than today, or if file is larger than 10MB
                if ($fileDate < $today || filesize($logFile) > 10 * 1024 * 1024) {
                    if (unlink($logFile)) {
                        $deletedLogFiles++;
                    } else {
                        $errors[] = 'Không thể xóa log file: ' . basename($logFile);
                    }
                } else {
                    // For today's log files, just truncate them instead of deleting
                    if (file_put_contents($logFile, '') !== false) {
                        $deletedLogFiles++;
                    } else {
                        $errors[] = 'Không thể xóa nội dung log file: ' . basename($logFile);
                    }
                }
            }
        }

        if ($deletedLogFiles > 0) {
            $cacheCleared[] = "Log files ({$deletedLogFiles} files)";
        }
    }

    // 5. Clear temporary files
    $tempDir = sys_get_temp_dir();
    $tempFiles = glob($tempDir . '/tro365_*');
    $deletedTempFiles = 0;
    foreach ($tempFiles as $file) {
        if (is_file($file) && (time() - filemtime($file)) > 3600) { // Older than 1 hour
            if (unlink($file)) {
                $deletedTempFiles++;
            }
        }
    }
    if ($deletedTempFiles > 0) {
        $cacheCleared[] = "Temp files ({$deletedTempFiles} files)";
    }

    // 6. Clear browser cache headers (for future requests)
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Log the cache clear action
    $currentUser = $auth->getCurrentUser();
    error_log("Cache cleared by admin user: {$currentUser['HoTen']} (ID: {$currentUser['ID']})");

    // Return success response
    $response = [
        'success' => true,
        'message' => 'Cache đã được xóa thành công',
        'cleared' => $cacheCleared,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Cache clear error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xóa cache: ' . $e->getMessage()
    ]);
}
?>
