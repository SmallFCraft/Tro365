<?php
/**
 * Image Optimization Service
 * Professional image optimization service with WebP/AVIF support
 * 
 * Tro365 - Website thuê trọ
 */

namespace Tro365\Services;

class ImageOptimizationService {
    
    private $quality = 85;
    private $maxWidth = 1920;
    private $maxHeight = 1080;
    private $enableWebP = true;
    private $enableAVIF = false;
    
    /**
     * Constructor
     */
    public function __construct($options = []) {
        $this->quality = $options['quality'] ?? 85;
        $this->maxWidth = $options['maxWidth'] ?? 1920;
        $this->maxHeight = $options['maxHeight'] ?? 1080;
        $this->enableWebP = $options['enableWebP'] ?? true;
        $this->enableAVIF = $options['enableAVIF'] ?? false;
    }
    
    /**
     * Optimize uploaded image automatically
     */
    public function optimizeUploadedImage($imagePath, $type = 'general') {
        $results = [
            'success' => false,
            'original_size' => 0,
            'optimized_size' => 0,
            'webp_size' => 0,
            'avif_size' => 0,
            'formats_created' => [],
            'savings' => 0,
            'error' => null
        ];
        
        try {
            if (!file_exists($imagePath)) {
                throw new \Exception("Image file not found: $imagePath");
            }
            
            $results['original_size'] = filesize($imagePath);
            
            // Check if it's an image file
            if (!$this->isImageFile($imagePath)) {
                throw new \Exception("File is not a valid image");
            }
            
            // Step 1: Optimize original image (resize + compress)
            $this->optimizeOriginalImage($imagePath);
            $results['optimized_size'] = filesize($imagePath);
            
            // Step 2: Create WebP version
            if ($this->enableWebP) {
                $webpPath = $this->createWebPVersion($imagePath);
                if ($webpPath && file_exists($webpPath)) {
                    $results['webp_size'] = filesize($webpPath);
                    $results['formats_created'][] = 'webp';
                }
            }
            
            // Step 3: Create AVIF version (if enabled)
            if ($this->enableAVIF && function_exists('imageavif')) {
                $avifPath = $this->createAVIFVersion($imagePath);
                if ($avifPath && file_exists($avifPath)) {
                    $results['avif_size'] = filesize($avifPath);
                    $results['formats_created'][] = 'avif';
                }
            }
            
            // Step 4: Create thumbnails for posts
            if ($type === 'posts') {
                $this->createThumbnails($imagePath);
            }
            
            // Calculate total savings
            $results['savings'] = $results['original_size'] - $results['optimized_size'];
            $results['success'] = true;
            
            // Simple logging to file
            $this->log("Optimization completed for: " . basename($imagePath) . 
                      " | Original: " . $this->formatBytes($results['original_size']) .
                      " | Optimized: " . $this->formatBytes($results['optimized_size']) .
                      " | Formats: " . implode(', ', $results['formats_created']));
            
        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
            $this->log("Optimization failed for: " . basename($imagePath) . " | Error: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Check if file is a valid image
     */
    private function isImageFile($imagePath) {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }
    
    /**
     * Optimize original image (resize + compress)
     */
    private function optimizeOriginalImage($imagePath) {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        
        // Load image
        $image = $this->loadImage($imagePath, $extension);
        if (!$image) {
            throw new \Exception("Cannot load image: $imagePath");
        }
        
        // Get current dimensions
        $currentWidth = imagesx($image);
        $currentHeight = imagesy($image);
        
        // Calculate new dimensions if needed
        $needsResize = ($currentWidth > $this->maxWidth || $currentHeight > $this->maxHeight);
        
        if ($needsResize) {
            $ratio = min($this->maxWidth / $currentWidth, $this->maxHeight / $currentHeight);
            $newWidth = (int)($currentWidth * $ratio);
            $newHeight = (int)($currentHeight * $ratio);
            
            // Create resized image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($extension === 'png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            }
            
            // Resize
            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $currentWidth, $currentHeight);
            
            // Save optimized image
            $this->saveImage($resizedImage, $imagePath, $extension);
            
            imagedestroy($resizedImage);
        } else {
            // Just re-save with compression
            $this->saveImage($image, $imagePath, $extension);
        }
        
        imagedestroy($image);
    }
    
    /**
     * Create WebP version
     */
    private function createWebPVersion($imagePath) {
        $pathInfo = pathinfo($imagePath);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        
        $extension = strtolower($pathInfo['extension']);
        $image = $this->loadImage($imagePath, $extension);
        
        if ($image && imagewebp($image, $webpPath, $this->quality)) {
            imagedestroy($image);
            return $webpPath;
        }
        
        if ($image) imagedestroy($image);
        return false;
    }
    
    /**
     * Create AVIF version
     */
    private function createAVIFVersion($imagePath) {
        if (!function_exists('imageavif')) {
            return false;
        }
        
        $pathInfo = pathinfo($imagePath);
        $avifPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.avif';
        
        $extension = strtolower($pathInfo['extension']);
        $image = $this->loadImage($imagePath, $extension);
        
        if ($image && imageavif($image, $avifPath, $this->quality)) {
            imagedestroy($image);
            return $avifPath;
        }
        
        if ($image) imagedestroy($image);
        return false;
    }
    
    /**
     * Create thumbnails for posts
     */
    private function createThumbnails($imagePath) {
        $pathInfo = pathinfo($imagePath);
        $thumbPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
        
        $extension = strtolower($pathInfo['extension']);
        $image = $this->loadImage($imagePath, $extension);
        
        if ($image) {
            $currentWidth = imagesx($image);
            $currentHeight = imagesy($image);
            
            // Calculate thumbnail dimensions (300x200)
            $thumbWidth = 300;
            $thumbHeight = 200;
            
            $ratio = min($thumbWidth / $currentWidth, $thumbHeight / $currentHeight);
            $newWidth = (int)($currentWidth * $ratio);
            $newHeight = (int)($currentHeight * $ratio);
            
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($extension === 'png') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }
            
            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $newWidth, $newHeight, $currentWidth, $currentHeight);
            
            $this->saveImage($thumbnail, $thumbPath, $extension);
            
            // Create WebP thumbnail too
            if ($this->enableWebP) {
                $thumbWebpPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['filename'] . '.webp';
                imagewebp($thumbnail, $thumbWebpPath, $this->quality);
            }
            
            imagedestroy($thumbnail);
            imagedestroy($image);
        }
    }
    
    /**
     * Load image based on extension
     */
    private function loadImage($imagePath, $extension) {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($imagePath);
            case 'png':
                return imagecreatefrompng($imagePath);
            case 'gif':
                return imagecreatefromgif($imagePath);
            case 'webp':
                return imagecreatefromwebp($imagePath);
            default:
                return false;
        }
    }
    
    /**
     * Save image based on extension
     */
    private function saveImage($image, $imagePath, $extension) {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $imagePath, $this->quality);
            case 'png':
                return imagepng($image, $imagePath, (int)(9 - ($this->quality / 100) * 9));
            case 'gif':
                return imagegif($image, $imagePath);
            case 'webp':
                return imagewebp($image, $imagePath, $this->quality);
            default:
                return false;
        }
    }
    
    /**
     * Simple logging to file
     */
    private function log($message) {
        $logFile = 'logs/image-optimization.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
