<?php

namespace Tro365\Services;

use Exception;

/**
 * Upload Service
 * Tro365 - Website thuê trọ
 *
 * Professional upload service with image optimization integration
 */
class Upload
{
    private $allowedTypes;
    private $maxSize;
    private $uploadPath;
    
    public function __construct()
    {
        // Pull dynamic values from DB settings if present, else fallback to constants
        $this->allowedTypes = \getUploadAllowedExtensionsArray();
        $this->maxSize = \getUploadMaxSizeBytes();
        $this->uploadPath = UPLOAD_PATH;

        // Debug upload configuration
        writeLog("Upload service initialized with optimized backend");
        writeLog("Upload config - Path: " . $this->uploadPath . ", Max size: " . $this->maxSize . ", Types: " . implode(',', $this->allowedTypes));
        writeLog("Upload path exists: " . (is_dir($this->uploadPath) ? 'YES' : 'NO'));
    }
    
    /**
     * Upload single file
     */
    public function uploadFile($file, $type = 'general', $customPath = null)
    {
        return $this->legacyUploadFile($file, $type, $customPath);
    }

    /**
     * Upload avatar with username as filename
     */
    public function uploadAvatar($file, $username)
    {
        return $this->legacyUploadAvatar($file, $username);
    }

    /**
     * Delete old avatar files for a user
     */
    private function deleteOldAvatar($username)
    {
        $avatarDir = $this->uploadPath . 'avatars';
        if (!is_dir($avatarDir)) {
            return;
        }

        // Common image extensions
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($extensions as $ext) {
            $oldFile = $avatarDir . '/' . $username . '.' . $ext;
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple($files, $type = 'general', $customPath = null)
    {
        return $this->legacyUploadMultiple($files, $type, $customPath);
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file)
    {
        writeLog("Validating file: " . json_encode($file));
        writeLog("Max size: " . $this->maxSize . ", Allowed types: " . implode(',', $this->allowedTypes));

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = $this->getUploadErrorMessage($file['error']);
            writeLog("Upload error: " . $errorMsg);
            throw new Exception($errorMsg);
        }

        // Check file size
        if ($file['size'] > $this->maxSize) {
            $errorMsg = "File quá lớn. Kích thước tối đa: " . formatFileSize($this->maxSize);
            writeLog("File size error: " . $errorMsg . " (actual: " . $file['size'] . ")");
            throw new Exception($errorMsg);
        }

        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        writeLog("File extension: " . $extension);

        if (!in_array($extension, $this->allowedTypes)) {
            $errorMsg = "Loại file không được phép. Chỉ chấp nhận: " . implode(', ', $this->allowedTypes);
            writeLog("File type error: " . $errorMsg);
            throw new Exception($errorMsg);
        }

        // Check if file is actually an image (for image uploads)
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            // Try getimagesize first (works for most formats including WebP on newer PHP)
            $imageInfo = getimagesize($file['tmp_name']);

            if ($imageInfo !== false) {
                // Standard validation worked
                writeLog("Image validation passed: " . $imageInfo[0] . "x" . $imageInfo[1] . " (type: " . $imageInfo['mime'] . ")");
            } else if ($extension === 'webp') {
                // Fallback WebP validation for older PHP versions
                writeLog("Fallback WebP validation...");
                $handle = fopen($file['tmp_name'], 'rb');
                if ($handle) {
                    $header = fread($handle, 12);
                    fclose($handle);

                    // WebP signature: RIFF....WEBP
                    if (strlen($header) >= 12 && substr($header, 0, 4) === 'RIFF' && substr($header, 8, 4) === 'WEBP') {
                        writeLog("WebP validation passed (fallback method)");
                    } else {
                        $errorMsg = "File không phải là WebP hợp lệ";
                        writeLog("WebP validation error: " . $errorMsg . " (header: " . bin2hex($header) . ")");
                        throw new Exception($errorMsg);
                    }
                } else {
                    $errorMsg = "Không thể đọc file WebP";
                    writeLog("WebP read error: " . $errorMsg);
                    throw new Exception($errorMsg);
                }
            } else {
                // Other image formats failed validation
                $errorMsg = "File không phải là hình ảnh hợp lệ";
                writeLog("Image validation error: " . $errorMsg);
                throw new Exception($errorMsg);
            }
        }

        writeLog("File validation passed");
        return true;
    }

    /**
     * Enhanced file validation using rakit/validation
     */
    public function validateFileEnhanced($file, $allowedTypes = null, $maxSizeMB = null)
    {
        try {
            $allowedTypes = $allowedTypes ?: $this->allowedTypes;
            $maxSizeMB = $maxSizeMB ?: 5;

            // Use ValidationHelper for enhanced validation
            $validation = \Tro365\Helpers\ValidationHelper::validateFileUploadEnhanced($file, $allowedTypes, $maxSizeMB);

            if (!$validation['valid']) {
                $errors = [];
                foreach ($validation['errors'] as $field => $fieldErrors) {
                    $errors = array_merge($errors, $fieldErrors);
                }
                throw new Exception(implode(', ', $errors));
            }

            writeLog("Enhanced file validation passed");
            return true;

        } catch (Exception $e) {
            writeLog("Enhanced file validation failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate file path
     */
    private function generateFilePath($file, $type, $customPath = null, $customFileName = null)
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Use custom filename if provided (for avatars)
        if ($customFileName) {
            $fileName = $customFileName;
        } else {
            $fileName = $this->generateFileName($file['name']);
        }

        if ($customPath) {
            $basePath = $this->uploadPath . $customPath;
        } else {
            // For avatars, don't use date subdirectories
            if ($type === 'avatars') {
                $basePath = $this->uploadPath . $type;
            } else {
                $basePath = $this->uploadPath . $type . '/' . date('Y/m');
            }
        }

        return $basePath . '/' . $fileName . '.' . $extension;
    }
    
    /**
     * Generate unique file name
     */
    private function generateFileName($originalName)
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $name = $this->sanitizeFileName($name);
        $timestamp = time();
        $random = generateRandomString(8);
        
        return $name . '_' . $timestamp . '_' . $random;
    }
    
    /**
     * Sanitize file name
     */
    private function sanitizeFileName($fileName)
    {
        // Remove Vietnamese characters
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
        
        $fileName = str_replace($vietnamese, $english, mb_strtolower($fileName, 'UTF-8'));
        $fileName = preg_replace('/[^a-z0-9\-_]/', '', $fileName);
        $fileName = preg_replace('/[-_]+/', '_', $fileName);
        $fileName = trim($fileName, '-_');
        
        return $fileName ?: 'file';
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "File quá lớn";
            case UPLOAD_ERR_PARTIAL:
                return "File chỉ được upload một phần";
            case UPLOAD_ERR_NO_FILE:
                return "Không có file nào được upload";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Thiếu thư mục tạm";
            case UPLOAD_ERR_CANT_WRITE:
                return "Không thể ghi file";
            case UPLOAD_ERR_EXTENSION:
                return "Upload bị dừng bởi extension";
            default:
                return "Lỗi upload không xác định";
        }
    }
    
    /**
     * Delete file
     */
    public function deleteFile($filePath)
    {
        return $this->legacyDeleteFile($filePath);
    }
    
    /**
     * Create thumbnail
     */
    public function createThumbnail($imagePath, $width = 300, $height = 200)
    {
        return $this->legacyCreateThumbnail($imagePath, $width, $height);
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Check if file exists
     */
    public function fileExists($filePath): bool
    {
        return file_exists($filePath);
    }

    /**
     * Get file size
     */
    public function getFileSize($filePath): int
    {
        return file_exists($filePath) ? filesize($filePath) : 0;
    }

    /**
     * Get file mime type
     */
    public function getMimeType($filePath): string
    {
        return file_exists($filePath) ? mime_content_type($filePath) ?: 'application/octet-stream' : 'application/octet-stream';
    }

    /**
     * Copy file
     */
    public function copyFile($source, $destination): bool
    {
        return file_exists($source) ? copy($source, $destination) : false;
    }

    /**
     * Move file
     */
    public function moveFile($source, $destination): bool
    {
        return file_exists($source) ? rename($source, $destination) : false;
    }

    /**
     * Create directory
     */
    public function createDirectory($path): bool
    {
        return is_dir($path) || mkdir($path, 0755, true);
    }

    /**
     * List directory contents
     */
    public function listDirectory($path = '', $recursive = false): array
    {
        // Simple directory listing
        $fullPath = $this->uploadPath . '/' . ltrim($path, '/');
        if (!is_dir($fullPath)) {
            return [];
        }

        $files = [];
        $iterator = $recursive ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath)) : new \DirectoryIterator($fullPath);

        foreach ($iterator as $file) {
            if ($file->isDot()) continue;

            $files[] = [
                'path' => str_replace($this->uploadPath . '/', '', $file->getPathname()),
                'type' => $file->isDir() ? 'dir' : 'file',
                'size' => $file->isFile() ? $file->getSize() : null,
                'timestamp' => $file->getMTime()
            ];
        }

        return $files;
    }

    /**
     * Clean up old files
     */
    public function cleanupOldFiles($directory = 'temp', $olderThanDays = 7): int
    {
        // Simple cleanup
        $fullPath = $this->uploadPath . '/' . ltrim($directory, '/');
        if (!is_dir($fullPath)) {
            return 0;
        }

        $cutoffTime = time() - ($olderThanDays * 24 * 60 * 60);
        $deleted = 0;

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                if (unlink($file->getPathname())) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    // ==================== CORE UPLOAD METHODS ====================

    /**
     * Upload file implementation
     */
    private function legacyUploadFile($file, $type = 'general', $customPath = null)
    {
        try {
            writeLog("Upload attempt (Legacy) - File: " . $file['name'] . ", Type: $type");
            writeLog("Upload path: " . $this->uploadPath);

            // Validate file
            $this->validateFile($file);

            // Generate file path
            $filePath = $this->generateFilePath($file, $type, $customPath);
            writeLog("Generated file path: " . $filePath);

            // Create directory if not exists
            $directory = dirname($filePath);
            writeLog("Target directory: " . $directory);

            if (!is_dir($directory)) {
                writeLog("Creating directory: " . $directory);
                if (!mkdir($directory, 0755, true)) {
                    throw new Exception("Không thể tạo thư mục upload: " . $directory);
                }
            }

            // Check if directory is writable
            if (!is_writable($directory)) {
                throw new Exception("Thư mục upload không có quyền ghi: " . $directory);
            }

            // Move uploaded file
            writeLog("Moving file from " . $file['tmp_name'] . " to " . $filePath);
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception("Không thể di chuyển file upload từ " . $file['tmp_name'] . " đến " . $filePath);
            }

            // Auto-optimize image using SimpleImageOptimizer service
            try {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {

                    // Use ImageOptimizationService for automatic optimization
                    require_once __DIR__ . '/ImageOptimizationService.php';

                    $optimizer = new \Tro365\Services\ImageOptimizationService([
                        'quality' => 85,
                        'maxWidth' => MAX_IMAGE_WIDTH ?? 1920,
                        'maxHeight' => MAX_IMAGE_HEIGHT ?? 1080,
                        'enableWebP' => true,
                        'enableAVIF' => function_exists('imageavif') // Auto-detect AVIF support
                    ]);

                    $results = $optimizer->optimizeUploadedImage($filePath, $type);

                    if ($results['success']) {
                        writeLog("Auto-optimization successful: " . basename($filePath) .
                                " | Saved: " . $this->formatBytes($results['savings']) .
                                " | Formats: " . implode(', ', $results['formats_created']));
                    } else {
                        writeLog("Auto-optimization failed: " . $results['error']);

                        // Fallback to old method
                        if (function_exists('resizeImageUnified')) {
                            resizeImageUnified($filePath, $filePath, MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT, 85, false);
                        }
                    }

                    // Try Spatie optimizer as additional optimization (best-effort)
                    try {
                        if (class_exists('\\Spatie\\ImageOptimizer\\OptimizerChainFactory')) {
                            \Spatie\ImageOptimizer\OptimizerChainFactory::create()->optimize($filePath);
                        }
                    } catch (\Throwable $e) {
                        writeLog('Spatie optimizer skipped: '.$e->getMessage());
                    }
                }
            } catch (Exception $ie) {
                writeLog("Auto image optimization failed: " . $ie->getMessage());

                // Fallback to basic resize if auto-optimization fails
                try {
                    if (function_exists('resizeImageUnified')) {
                        resizeImageUnified($filePath, $filePath, MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT, 85, false);
                    }
                } catch (Exception $fallbackError) {
                    writeLog("Fallback optimization also failed: " . $fallbackError->getMessage());
                }
            }

            // Generate web path
            $webPath = '/' . str_replace('\\', '/', $filePath);

            writeLog("Upload successful (Legacy) - Web path: " . $webPath);

            return [
                'success' => true,
                'file_path' => $filePath,
                'web_path' => $webPath,
                'file_name' => basename($filePath),
                'file_size' => $file['size'],
                'file_type' => $file['type']
            ];

        } catch (Exception $e) {
            writeLog("Upload failed (Legacy): " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload avatar implementation
     */
    private function legacyUploadAvatar($file, $username)
    {
        try {
            writeLog("Avatar upload attempt (Legacy) - User: $username");

            // Validate file
            $this->validateFile($file);

            // Delete old avatar if exists
            $this->deleteOldAvatar($username);

            // Generate file path with username as filename
            $filePath = $this->generateFilePath($file, 'avatars', null, $username);

            // Create directory if not exists
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception("Không thể di chuyển file upload");
            }

            // Optimize avatar image (if applicable)
            try {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    if (class_exists('\Tro365\Helpers\ImageHelper')) {
                        \Tro365\Helpers\ImageHelper::optimizeForWeb($filePath, 85, 512, 512);
                    } else {
                        resizeImageUnified($filePath, $filePath, 512, 512, 85, false);
                    }
                    // Try Spatie optimizer (best-effort)
                    try {
                        if (class_exists('\\Spatie\\ImageOptimizer\\OptimizerChainFactory')) {
                            \Spatie\ImageOptimizer\OptimizerChainFactory::create()->optimize($filePath);
                        }
                    } catch (\Throwable $e) { writeLog('Spatie optimizer skipped: '.$e->getMessage()); }
                }
            } catch (Exception $ie) {}

            // Generate web path
            $webPath = '/' . str_replace('\\', '/', $filePath);

            writeLog("Avatar upload successful (Legacy) - Web path: " . $webPath);

            return [
                'success' => true,
                'file_path' => $filePath,
                'web_path' => $webPath,
                'file_name' => basename($filePath),
                'file_size' => $file['size'],
                'file_type' => $file['type']
            ];

        } catch (Exception $e) {
            writeLog("Avatar upload failed (Legacy): " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload multiple files implementation
     */
    private function legacyUploadMultiple($files, $type = 'general', $customPath = null)
    {
        $results = [];

        writeLog("Multiple upload attempt (Legacy) - Type: $type");

        // Handle different file input formats
        if (isset($files['name']) && is_array($files['name'])) {
            // Multiple files from single input
            $fileCount = count($files['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];

                if ($file['error'] === UPLOAD_ERR_OK) {
                    $results[] = $this->legacyUploadFile($file, $type, $customPath);
                }
            }
        } else {
            // Single file
            $results[] = $this->legacyUploadFile($files, $type, $customPath);
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        writeLog("Multiple upload completed (Legacy) - Success: $successCount/" . count($results));

        return $results;
    }

    /**
     * Delete file implementation
     */
    private function legacyDeleteFile($filePath)
    {
        try {
            writeLog("Delete file attempt (Legacy) - Path: $filePath");

            if (file_exists($filePath)) {
                $result = unlink($filePath);
                if ($result) {
                    writeLog("File deleted successfully (Legacy)");
                } else {
                    writeLog("File deletion failed (Legacy)");
                }
                return $result;
            }

            writeLog("File not found, considering deleted (Legacy)");
            return true;

        } catch (Exception $e) {
            writeLog("Delete file exception (Legacy): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create thumbnail implementation
     */
    private function legacyCreateThumbnail($imagePath, $width = 300, $height = 200)
    {
        try {
            writeLog("Create thumbnail attempt (Legacy) - Path: $imagePath");

            // Use ImageHelper for thumbnail creation to avoid code duplication
            if (class_exists('\Tro365\Helpers\ImageHelper')) {
                $result = \Tro365\Helpers\ImageHelper::createThumbnail($imagePath, null, $width, $height);
                writeLog("Thumbnail created successfully (Legacy) using ImageHelper");
                return $result;
            } else {
                // Fallback to resizeImageUnified function
                $result = resizeImageUnified($imagePath, null, $width, $height, 85, false);
                writeLog("Thumbnail created successfully (Legacy) using resizeImageUnified");
                return $result;
            }

        } catch (Exception $e) {
            writeLog("Create thumbnail exception (Legacy): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
