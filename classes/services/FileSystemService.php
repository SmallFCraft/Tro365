<?php

namespace Tro365\Services;

use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use League\Flysystem\FilesystemException;

/**
 * FileSystem Service using League Flysystem
 * Tro365 - Website thuê trọ
 * 
 * Replaces manual file operations with a modern, unified file system abstraction
 */
class FileSystemService
{
    private Filesystem $filesystem;
    private string $basePath;
    private array $allowedExtensions;
    private int $maxFileSize;
    
    public function __construct(string $basePath = null)
    {
        $this->basePath = $basePath ?: dirname(__DIR__, 2) . '/assets/uploads';
        $this->allowedExtensions = $this->getAllowedExtensions();
        $this->maxFileSize = $this->getMaxFileSize();
        
        // Create adapter with proper permissions
        $adapter = new LocalFilesystemAdapter(
            $this->basePath,
            PortableVisibilityConverter::fromArray([
                'file' => [
                    'public' => 0644,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0700,
                ],
            ]),
            LOCK_EX,
            LocalFilesystemAdapter::DISALLOW_LINKS
        );
        
        $this->filesystem = new Filesystem($adapter);
        
        // Ensure base directories exist
        $this->ensureDirectoriesExist();
    }
    
    /**
     * Upload a single file
     */
    public function uploadFile(array $file, string $type = 'general', string $customPath = null): array
    {
        try {
            // Validate file
            $this->validateFile($file);
            
            // Generate file path
            $filePath = $this->generateFilePath($file, $type, $customPath);
            
            // Read file content
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                throw new Exception("Không thể đọc file upload");
            }
            
            // Write file using Flysystem
            $this->filesystem->write($filePath, $content, [
                'visibility' => Visibility::PUBLIC
            ]);
            
            // Optimize image if applicable
            $fullPath = $this->getFullPath($filePath);
            if ($this->isImageFile($file['name'])) {
                $this->optimizeImage($fullPath, $type);
            }
            
            return [
                'success' => true,
                'file_path' => $fullPath,
                'web_path' => $this->getWebPath($filePath),
                'file_name' => basename($filePath),
                'file_size' => $file['size'],
                'file_type' => $file['type']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload avatar with username as filename
     */
    public function uploadAvatar(array $file, string $username): array
    {
        try {
            // Validate file
            $this->validateFile($file);
            
            // Delete old avatar if exists
            $this->deleteOldAvatar($username);
            
            // Generate file path with username as filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filePath = "avatars/{$username}.{$extension}";
            
            // Read and write file
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                throw new Exception("Không thể đọc file upload");
            }
            
            $this->filesystem->write($filePath, $content, [
                'visibility' => Visibility::PUBLIC
            ]);
            
            // Optimize avatar image
            $fullPath = $this->getFullPath($filePath);
            if ($this->isImageFile($file['name'])) {
                $this->optimizeImage($fullPath, 'avatars', 512, 512);
            }
            
            return [
                'success' => true,
                'file_path' => $fullPath,
                'web_path' => $this->getWebPath($filePath),
                'file_name' => basename($filePath),
                'file_size' => $file['size'],
                'file_type' => $file['type']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files, string $type = 'general', string $customPath = null): array
    {
        $results = [];
        
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
                    $results[] = $this->uploadFile($file, $type, $customPath);
                }
            }
        } else {
            // Single file
            $results[] = $this->uploadFile($files, $type, $customPath);
        }
        
        return $results;
    }
    
    /**
     * Delete a file
     */
    public function deleteFile(string $filePath): bool
    {
        try {
            // Convert absolute path to relative if needed
            $relativePath = $this->getRelativePath($filePath);
            
            if ($this->filesystem->fileExists($relativePath)) {
                $this->filesystem->delete($relativePath);
                
                // Also try to delete thumbnail if it's an image
                $this->deleteThumbnail($relativePath);
                
                return true;
            }
            
            return true; // File doesn't exist, consider it deleted
            
        } catch (FilesystemException $e) {
            error_log("Failed to delete file: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if file exists
     */
    public function fileExists(string $filePath): bool
    {
        try {
            $relativePath = $this->getRelativePath($filePath);
            return $this->filesystem->fileExists($relativePath);
        } catch (FilesystemException $e) {
            return false;
        }
    }
    
    /**
     * Get file size
     */
    public function getFileSize(string $filePath): int
    {
        try {
            $relativePath = $this->getRelativePath($filePath);
            return $this->filesystem->fileSize($relativePath);
        } catch (FilesystemException $e) {
            return 0;
        }
    }
    
    /**
     * Get file mime type
     */
    public function getMimeType(string $filePath): string
    {
        try {
            $relativePath = $this->getRelativePath($filePath);
            return $this->filesystem->mimeType($relativePath);
        } catch (FilesystemException $e) {
            return 'application/octet-stream';
        }
    }
    
    /**
     * Copy file
     */
    public function copyFile(string $source, string $destination): bool
    {
        try {
            $sourceRelative = $this->getRelativePath($source);
            $destinationRelative = $this->getRelativePath($destination);
            
            $this->filesystem->copy($sourceRelative, $destinationRelative);
            return true;
            
        } catch (FilesystemException $e) {
            error_log("Failed to copy file: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Move file
     */
    public function moveFile(string $source, string $destination): bool
    {
        try {
            $sourceRelative = $this->getRelativePath($source);
            $destinationRelative = $this->getRelativePath($destination);
            
            $this->filesystem->move($sourceRelative, $destinationRelative);
            return true;
            
        } catch (FilesystemException $e) {
            error_log("Failed to move file: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create directory
     */
    public function createDirectory(string $path): bool
    {
        try {
            $relativePath = $this->getRelativePath($path);
            
            if (!$this->filesystem->directoryExists($relativePath)) {
                $this->filesystem->createDirectory($relativePath, [
                    'visibility' => Visibility::PUBLIC
                ]);
            }
            
            return true;
            
        } catch (FilesystemException $e) {
            error_log("Failed to create directory: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * List directory contents
     */
    public function listDirectory(string $path = '', bool $recursive = false): array
    {
        try {
            $relativePath = $this->getRelativePath($path);
            
            if ($recursive) {
                $listing = $this->filesystem->listContents($relativePath, true);
            } else {
                $listing = $this->filesystem->listContents($relativePath, false);
            }
            
            $files = [];
            foreach ($listing as $item) {
                $files[] = [
                    'path' => $item->path(),
                    'type' => $item->type(),
                    'size' => $item instanceof \League\Flysystem\FileAttributes ? $item->fileSize() : null,
                    'timestamp' => $item instanceof \League\Flysystem\FileAttributes ? $item->lastModified() : null,
                ];
            }
            
            return $files;
            
        } catch (FilesystemException $e) {
            error_log("Failed to list directory: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up old files (for maintenance)
     */
    public function cleanupOldFiles(string $directory = 'temp', int $olderThanDays = 7): int
    {
        try {
            $cutoffTime = time() - ($olderThanDays * 24 * 60 * 60);
            $deleted = 0;

            $files = $this->listDirectory($directory, true);

            foreach ($files as $file) {
                if ($file['type'] === 'file' && $file['timestamp'] && $file['timestamp'] < $cutoffTime) {
                    if ($this->deleteFile($file['path'])) {
                        $deleted++;
                    }
                }
            }

            return $deleted;

        } catch (Exception $e) {
            error_log("Failed to cleanup old files: " . $e->getMessage());
            return 0;
        }
    }

    // ==================== PRIVATE METHODS ====================

    /**
     * Validate uploaded file
     */
    private function validateFile(array $file): void
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / (1024 * 1024), 2);
            throw new Exception("File quá lớn. Kích thước tối đa: {$maxSizeMB}MB");
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception("Định dạng file không được phép. Chỉ chấp nhận: " . implode(', ', $this->allowedExtensions));
        }

        // Check if file is actually uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception("File không hợp lệ");
        }
    }

    /**
     * Generate file path for upload
     */
    private function generateFilePath(array $file, string $type, string $customPath = null): string
    {
        if ($customPath) {
            return $customPath;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;

        // Create date-based directory structure
        $dateDir = date('Y/m');

        return "{$type}/{$dateDir}/{$filename}";
    }

    /**
     * Get full filesystem path
     */
    private function getFullPath(string $relativePath): string
    {
        return $this->basePath . '/' . ltrim($relativePath, '/');
    }

    /**
     * Get web-accessible path
     */
    private function getWebPath(string $relativePath): string
    {
        return '/assets/uploads/' . ltrim($relativePath, '/');
    }

    /**
     * Convert absolute path to relative path
     */
    private function getRelativePath(string $path): string
    {
        // If path is already relative, return as is
        if (!str_starts_with($path, '/') && !str_contains($path, ':')) {
            return $path;
        }

        // Remove base path if present
        if (str_starts_with($path, $this->basePath)) {
            return ltrim(substr($path, strlen($this->basePath)), '/');
        }

        // Remove web path prefix if present
        if (str_starts_with($path, '/assets/uploads/')) {
            return ltrim(substr($path, strlen('/assets/uploads/')), '/');
        }

        return ltrim($path, '/');
    }

    /**
     * Check if file is an image
     */
    private function isImageFile(string $filename): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    }

    /**
     * Optimize image using existing ImageHelper
     */
    private function optimizeImage(string $fullPath, string $type, int $maxWidth = null, int $maxHeight = null): void
    {
        try {
            $maxWidth = $maxWidth ?: ($type === 'avatars' ? 512 : MAX_IMAGE_WIDTH);
            $maxHeight = $maxHeight ?: ($type === 'avatars' ? 512 : MAX_IMAGE_HEIGHT);

            if (class_exists('\Tro365\Helpers\ImageHelper')) {
                \Tro365\Helpers\ImageHelper::optimizeForWeb($fullPath, 85, $maxWidth, $maxHeight);

                // Create thumbnail for post images
                if ($type === 'posts') {
                    \Tro365\Helpers\ImageHelper::createThumbnail($fullPath, null, 300, 200);
                }
            } else {
                // Fallback to helper function
                resizeImageUnified($fullPath, $fullPath, $maxWidth, $maxHeight, 85, false);
            }

            // Try Spatie optimizer (best-effort)
            try {
                if (class_exists('\\Spatie\\ImageOptimizer\\OptimizerChainFactory')) {
                    \Spatie\ImageOptimizer\OptimizerChainFactory::create()->optimize($fullPath);
                }
            } catch (\Throwable $e) {
                // Ignore optimizer errors
            }

        } catch (Exception $e) {
            // Log but don't fail the upload
            error_log("Image optimization failed: " . $e->getMessage());
        }
    }

    /**
     * Delete old avatar files for a user
     */
    private function deleteOldAvatar(string $username): void
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($extensions as $ext) {
            $oldFile = "avatars/{$username}.{$ext}";
            if ($this->filesystem->fileExists($oldFile)) {
                try {
                    $this->filesystem->delete($oldFile);
                } catch (FilesystemException $e) {
                    // Ignore deletion errors for old files
                }
            }
        }
    }

    /**
     * Delete thumbnail if exists
     */
    private function deleteThumbnail(string $filePath): void
    {
        if ($this->isImageFile($filePath)) {
            $pathInfo = pathinfo($filePath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];

            if ($this->filesystem->fileExists($thumbnailPath)) {
                try {
                    $this->filesystem->delete($thumbnailPath);
                } catch (FilesystemException $e) {
                    // Ignore thumbnail deletion errors
                }
            }
        }
    }

    /**
     * Get allowed file extensions from config
     */
    private function getAllowedExtensions(): array
    {
        if (function_exists('getUploadAllowedExtensionsArray')) {
            return getUploadAllowedExtensionsArray();
        }

        // Fallback to default extensions
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt'];
    }

    /**
     * Get maximum file size from config
     */
    private function getMaxFileSize(): int
    {
        if (function_exists('getUploadMaxSizeMB')) {
            return (int)(getUploadMaxSizeMB() * 1024 * 1024);
        }

        // Fallback to 10MB
        return 10 * 1024 * 1024;
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File quá lớn (vượt quá upload_max_filesize)';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File quá lớn (vượt quá MAX_FILE_SIZE)';
            case UPLOAD_ERR_PARTIAL:
                return 'File chỉ được upload một phần';
            case UPLOAD_ERR_NO_FILE:
                return 'Không có file nào được upload';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Thiếu thư mục tạm';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Không thể ghi file lên disk';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload bị dừng bởi extension';
            default:
                return 'Lỗi upload không xác định';
        }
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectoriesExist(): void
    {
        $directories = ['posts', 'avatars', 'documents', 'temp'];

        foreach ($directories as $dir) {
            $this->createDirectory($dir);
        }
    }
}
