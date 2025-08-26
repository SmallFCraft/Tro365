<?php

namespace Tro365\Helpers;

use Exception;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * Image Helper Class
 * Tro365 - Website thuê trọ
 */
class ImageHelper
{
    /**
     * Resize image to specific dimensions
     */
    public static function resize($imagePath, $width, $height, $outputPath = null)
    {
        try {
            $outputPath = $outputPath ?: $imagePath;
            
            $image = Image::make($imagePath);
            $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            $image->save($outputPath);
            return $outputPath;
            
        } catch (Exception $e) {
            throw new Exception("Lỗi resize ảnh: " . $e->getMessage());
        }
    }
    
    /**
     * Create thumbnail
     */
    public static function createThumbnail($imagePath, $thumbnailPath = null, $width = null, $height = null)
    {
        $width = $width ?: THUMBNAIL_WIDTH;
        $height = $height ?: THUMBNAIL_HEIGHT;
        
        if (!$thumbnailPath) {
            $pathInfo = pathinfo($imagePath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
        }
        
        return self::resize($imagePath, $width, $height, $thumbnailPath);
    }
    
    /**
     * Optimize image for web
     */
    public static function optimizeForWeb($imagePath, $quality = 85, $maxWidth = null, $maxHeight = null)
    {
        try {
            $maxWidth = $maxWidth ?: MAX_IMAGE_WIDTH;
            $maxHeight = $maxHeight ?: MAX_IMAGE_HEIGHT;
            
            $image = Image::make($imagePath);
            
            // Resize if too large
            if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
                $image->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Save with compression
            $image->save($imagePath, $quality);
            return $imagePath;
            
        } catch (Exception $e) {
            throw new Exception("Lỗi tối ưu ảnh: " . $e->getMessage());
        }
    }
    
    /**
     * Get image dimensions
     */
    public static function getDimensions($imagePath)
    {
        try {
            $image = Image::make($imagePath);
            return [
                'width' => $image->width(),
                'height' => $image->height()
            ];
        } catch (Exception $e) {
            throw new Exception("Lỗi đọc kích thước ảnh: " . $e->getMessage());
        }
    }
    
    /**
     * Convert image format
     */
    public static function convertFormat($imagePath, $format, $outputPath = null)
    {
        try {
            $outputPath = $outputPath ?: $imagePath;
            
            $image = Image::make($imagePath);
            $image->encode($format);
            $image->save($outputPath);
            
            return $outputPath;
            
        } catch (Exception $e) {
            throw new Exception("Lỗi chuyển đổi định dạng ảnh: " . $e->getMessage());
        }
    }
    
    /**
     * Add watermark to image
     */
    public static function addWatermark($imagePath, $watermarkPath, $position = 'bottom-right', $opacity = 50)
    {
        try {
            $image = Image::make($imagePath);
            $watermark = Image::make($watermarkPath);
            
            // Set watermark opacity
            $watermark->opacity($opacity);
            
            // Insert watermark
            $image->insert($watermark, $position, 10, 10);
            $image->save($imagePath);
            
            return $imagePath;
            
        } catch (Exception $e) {
            throw new Exception("Lỗi thêm watermark: " . $e->getMessage());
        }
    }
    
    /**
     * Validate image file
     */
    public static function validateImage($file)
    {
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes)) {
            throw new Exception("Định dạng ảnh không được hỗ trợ. Chỉ chấp nhận: " . implode(', ', $allowedTypes));
        }
        
        // Check if it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            throw new Exception("File không phải là ảnh hợp lệ");
        }
        
        return true;
    }
    
    /**
     * Generate unique filename for image
     */
    public static function generateUniqueFilename($originalName, $prefix = '')
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = $prefix . uniqid() . '_' . time() . '.' . $extension;
        return $filename;
    }
    
    /**
     * Delete image and its thumbnail
     */
    public static function deleteImage($imagePath)
    {
        try {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            
            // Try to delete thumbnail
            $pathInfo = pathinfo($imagePath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Lỗi xóa ảnh: " . $e->getMessage());
        }
    }
}
