<?php

namespace Tro365\Services;

use Exception;

/**
 * Flysystem Upload Service - Drop-in replacement for Upload class
 * Tro365 - Website thuê trọ
 * 
 * This class provides backward compatibility with the existing Upload class
 * while using the new FileSystemService internally
 */
class FlysystemUpload
{
    private FileSystemService $fileSystem;
    private string $uploadPath;
    
    public function __construct(string $uploadPath = null)
    {
        $this->uploadPath = $uploadPath ?: dirname(__DIR__, 2) . '/assets/uploads/';
        $this->fileSystem = new FileSystemService(rtrim($this->uploadPath, '/'));
    }
    
    /**
     * Upload single file - Compatible with existing Upload::uploadFile()
     */
    public function uploadFile($file, $type = 'general', $customPath = null)
    {
        try {
            writeLog("FlysystemUpload: Upload attempt - File: " . $file['name'] . ", Type: $type");
            
            $result = $this->fileSystem->uploadFile($file, $type, $customPath);
            
            if ($result['success']) {
                writeLog("FlysystemUpload: Upload successful - Path: " . $result['file_path']);
                return $result;
            } else {
                writeLog("FlysystemUpload: Upload failed - Error: " . $result['error']);
                return $result;
            }
            
        } catch (Exception $e) {
            writeLog("FlysystemUpload: Exception - " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload avatar - Compatible with existing Upload::uploadAvatar()
     */
    public function uploadAvatar($file, $username)
    {
        try {
            writeLog("FlysystemUpload: Avatar upload attempt - User: $username");
            
            $result = $this->fileSystem->uploadAvatar($file, $username);
            
            if ($result['success']) {
                writeLog("FlysystemUpload: Avatar upload successful - Path: " . $result['file_path']);
            } else {
                writeLog("FlysystemUpload: Avatar upload failed - Error: " . $result['error']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            writeLog("FlysystemUpload: Avatar upload exception - " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload multiple files - Compatible with existing Upload::uploadMultiple()
     */
    public function uploadMultiple($files, $type = 'general', $customPath = null)
    {
        try {
            writeLog("FlysystemUpload: Multiple upload attempt - Type: $type");
            
            $results = $this->fileSystem->uploadMultiple($files, $type, $customPath);
            
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            writeLog("FlysystemUpload: Multiple upload completed - Success: $successCount/" . count($results));
            
            return $results;
            
        } catch (Exception $e) {
            writeLog("FlysystemUpload: Multiple upload exception - " . $e->getMessage());
            return [[
                'success' => false,
                'error' => $e->getMessage()
            ]];
        }
    }
    
    /**
     * Delete file - Compatible with existing Upload::deleteFile()
     */
    public function deleteFile($filePath)
    {
        try {
            writeLog("FlysystemUpload: Delete file attempt - Path: $filePath");
            
            $result = $this->fileSystem->deleteFile($filePath);
            
            if ($result) {
                writeLog("FlysystemUpload: File deleted successfully");
            } else {
                writeLog("FlysystemUpload: File deletion failed");
            }
            
            return $result;
            
        } catch (Exception $e) {
            writeLog("FlysystemUpload: Delete file exception - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create thumbnail - Compatible with existing Upload::createThumbnail()
     */
    public function createThumbnail($imagePath, $width = 300, $height = 200)
    {
        try {
            writeLog("FlysystemUpload: Create thumbnail attempt - Path: $imagePath");
            
            // Use ImageHelper for thumbnail creation
            if (class_exists('\Tro365\Helpers\ImageHelper')) {
                $result = \Tro365\Helpers\ImageHelper::createThumbnail($imagePath, null, $width, $height);
                writeLog("FlysystemUpload: Thumbnail created successfully");
                return $result;
            } else {
                writeLog("FlysystemUpload: ImageHelper not available, using fallback");
                return resizeImageUnified($imagePath, null, $width, $height, 85, false);
            }
            
        } catch (Exception $e) {
            writeLog("FlysystemUpload: Create thumbnail exception - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if file exists
     */
    public function fileExists($filePath): bool
    {
        return $this->fileSystem->fileExists($filePath);
    }
    
    /**
     * Get file size
     */
    public function getFileSize($filePath): int
    {
        return $this->fileSystem->getFileSize($filePath);
    }
    
    /**
     * Get file mime type
     */
    public function getMimeType($filePath): string
    {
        return $this->fileSystem->getMimeType($filePath);
    }
    
    /**
     * Copy file
     */
    public function copyFile($source, $destination): bool
    {
        return $this->fileSystem->copyFile($source, $destination);
    }
    
    /**
     * Move file
     */
    public function moveFile($source, $destination): bool
    {
        return $this->fileSystem->moveFile($source, $destination);
    }
    
    /**
     * Create directory
     */
    public function createDirectory($path): bool
    {
        return $this->fileSystem->createDirectory($path);
    }
    
    /**
     * List directory contents
     */
    public function listDirectory($path = '', $recursive = false): array
    {
        return $this->fileSystem->listDirectory($path, $recursive);
    }
    
    /**
     * Clean up old files
     */
    public function cleanupOldFiles($directory = 'temp', $olderThanDays = 7): int
    {
        return $this->fileSystem->cleanupOldFiles($directory, $olderThanDays);
    }
    
    /**
     * Get upload path
     */
    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }
    
    /**
     * Get FileSystemService instance for advanced operations
     */
    public function getFileSystem(): FileSystemService
    {
        return $this->fileSystem;
    }
    
    // ==================== LEGACY COMPATIBILITY METHODS ====================
    
    /**
     * Legacy method compatibility - validateFile
     */
    public function validateFile($file): bool
    {
        try {
            // Use reflection to call private method for validation
            $reflection = new \ReflectionClass($this->fileSystem);
            $method = $reflection->getMethod('validateFile');
            $method->setAccessible(true);
            $method->invoke($this->fileSystem, $file);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Legacy method compatibility - generateFilePath
     */
    public function generateFilePath($file, $type, $customPath = null): string
    {
        try {
            // Use reflection to call private method
            $reflection = new \ReflectionClass($this->fileSystem);
            $method = $reflection->getMethod('generateFilePath');
            $method->setAccessible(true);
            return $method->invoke($this->fileSystem, $file, $type, $customPath);
        } catch (Exception $e) {
            // Fallback to simple generation
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $dateDir = date('Y/m');
            return "{$type}/{$dateDir}/{$filename}";
        }
    }
    
    /**
     * Legacy method compatibility - isImageFile
     */
    public function isImageFile($filename): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    }
}
