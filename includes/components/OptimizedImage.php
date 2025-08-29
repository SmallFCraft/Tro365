<?php
/**
 * Optimized Image Component
 * Generates picture elements with modern format support for better performance
 * 
 * Tro365 - Website thuê trọ
 */

class OptimizedImage {
    
    /**
     * Render optimized image with picture element
     * 
     * @param string $imagePath Base image path
     * @param string $alt Alt text
     * @param array $options Configuration options
     * @return string HTML output
     */
    public static function render($imagePath, $alt = '', $options = []) {
        // Default options
        $defaults = [
            'width' => null,
            'height' => null,
            'class' => '',
            'loading' => 'lazy',
            'sizes' => '(max-width: 768px) 100vw, 50vw',
            'responsive_sizes' => [400, 600, 800, 1200],
            'use_picture' => true,
            'quality' => 85,
            'attributes' => []
        ];
        
        $options = array_merge($defaults, $options);
        
        // If picture element is disabled, use simple img tag
        if (!$options['use_picture']) {
            return self::renderSimpleImage($imagePath, $alt, $options);
        }
        
        return self::renderPictureElement($imagePath, $alt, $options);
    }
    
    /**
     * Render picture element with modern format support
     */
    private static function renderPictureElement($imagePath, $alt, $options) {
        $pathInfo = pathinfo($imagePath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        
        // Generate paths for different formats
        $avifPath = $directory . '/' . $filename . '.avif';
        $webpPath = $directory . '/' . $filename . '.webp';
        
        $html = '<picture>';
        
        // AVIF source (best compression) - check if file exists
        $avifFullPath = $_SERVER['DOCUMENT_ROOT'] . $avifPath;
        if (file_exists($avifFullPath) && filesize($avifFullPath) > 0) {
            $avifSrcset = self::generateSrcset($avifPath, $options['responsive_sizes']);
            $html .= '<source srcset="' . htmlspecialchars($avifSrcset) . '" type="image/avif" sizes="' . htmlspecialchars($options['sizes']) . '">';
        }

        // WebP source (fallback) - check if file exists
        $webpFullPath = $_SERVER['DOCUMENT_ROOT'] . $webpPath;
        if (file_exists($webpFullPath) && filesize($webpFullPath) > 0) {
            $webpSrcset = self::generateSrcset($webpPath, $options['responsive_sizes']);
            $html .= '<source srcset="' . htmlspecialchars($webpSrcset) . '" type="image/webp" sizes="' . htmlspecialchars($options['sizes']) . '">';
        }
        
        // Original format (final fallback)
        $originalSrcset = self::generateSrcset($imagePath, $options['responsive_sizes']);
        
        $html .= '<img';
        $html .= ' src="' . htmlspecialchars($imagePath) . '"';
        $html .= ' srcset="' . htmlspecialchars($originalSrcset) . '"';
        $html .= ' alt="' . htmlspecialchars($alt) . '"';
        
        if ($options['width']) {
            $html .= ' width="' . (int)$options['width'] . '"';
        }
        if ($options['height']) {
            $html .= ' height="' . (int)$options['height'] . '"';
        }
        if ($options['class']) {
            $html .= ' class="' . htmlspecialchars($options['class']) . '"';
        }
        if ($options['loading']) {
            $html .= ' loading="' . htmlspecialchars($options['loading']) . '"';
        }
        
        $html .= ' sizes="' . htmlspecialchars($options['sizes']) . '"';
        
        // Add custom attributes
        foreach ($options['attributes'] as $key => $value) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        $html .= '>';
        $html .= '</picture>';
        
        return $html;
    }
    
    /**
     * Render simple img tag with optimization
     */
    private static function renderSimpleImage($imagePath, $alt, $options) {
        $optimizedUrl = getOptimizedImageUrl($imagePath, $options['width'], $options['height']);
        $srcset = self::generateSrcset($imagePath, $options['responsive_sizes']);
        
        $html = '<img';
        $html .= ' src="' . htmlspecialchars($optimizedUrl) . '"';
        $html .= ' srcset="' . htmlspecialchars($srcset) . '"';
        $html .= ' alt="' . htmlspecialchars($alt) . '"';
        
        if ($options['width']) {
            $html .= ' width="' . (int)$options['width'] . '"';
        }
        if ($options['height']) {
            $html .= ' height="' . (int)$options['height'] . '"';
        }
        if ($options['class']) {
            $html .= ' class="' . htmlspecialchars($options['class']) . '"';
        }
        if ($options['loading']) {
            $html .= ' loading="' . htmlspecialchars($options['loading']) . '"';
        }
        
        $html .= ' sizes="' . htmlspecialchars($options['sizes']) . '"';
        
        // Add custom attributes
        foreach ($options['attributes'] as $key => $value) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        $html .= '>';
        
        return $html;
    }
    
    /**
     * Generate srcset for responsive images
     */
    private static function generateSrcset($imagePath, $sizes) {
        $srcset = [];
        foreach ($sizes as $size) {
            $optimizedUrl = getOptimizedImageUrl($imagePath, $size);
            $srcset[] = $optimizedUrl . ' ' . $size . 'w';
        }
        return implode(', ', $srcset);
    }
    
    /**
     * Render optimized image for post cards
     */
    public static function renderPostImage($imagePath, $alt = '', $options = []) {
        $defaults = [
            'width' => 400,
            'height' => 300,
            'class' => 'post-image img-fluid',
            'loading' => 'lazy',
            'sizes' => '(max-width: 576px) 100vw, (max-width: 768px) 50vw, (max-width: 992px) 33vw, 25vw',
            'responsive_sizes' => [300, 400, 600, 800]
        ];
        
        $options = array_merge($defaults, $options);
        return self::render($imagePath, $alt, $options);
    }
    
    /**
     * Render optimized image for hero sections
     */
    public static function renderHeroImage($imagePath, $alt = '', $options = []) {
        $defaults = [
            'width' => 1200,
            'height' => 600,
            'class' => 'hero-image img-fluid',
            'loading' => 'eager', // Hero images should load immediately
            'sizes' => '100vw',
            'responsive_sizes' => [800, 1200, 1600, 2000]
        ];
        
        $options = array_merge($defaults, $options);
        return self::render($imagePath, $alt, $options);
    }
    
    /**
     * Render optimized avatar image
     */
    public static function renderAvatar($imagePath, $alt = '', $options = []) {
        $defaults = [
            'width' => 100,
            'height' => 100,
            'class' => 'avatar-image rounded-circle',
            'loading' => 'lazy',
            'sizes' => '100px',
            'responsive_sizes' => [50, 100, 150, 200]
        ];
        
        $options = array_merge($defaults, $options);
        return self::render($imagePath, $alt, $options);
    }
    
    /**
     * Render optimized thumbnail image
     */
    public static function renderThumbnail($imagePath, $alt = '', $options = []) {
        $defaults = [
            'width' => 150,
            'height' => 150,
            'class' => 'thumbnail-image img-fluid',
            'loading' => 'lazy',
            'sizes' => '150px',
            'responsive_sizes' => [100, 150, 200, 300]
        ];
        
        $options = array_merge($defaults, $options);
        return self::render($imagePath, $alt, $options);
    }
}

/**
 * Helper function for easy access
 */
function optimizedImage($imagePath, $alt = '', $options = []) {
    return OptimizedImage::render($imagePath, $alt, $options);
}

function optimizedPostImage($imagePath, $alt = '', $options = []) {
    return OptimizedImage::renderPostImage($imagePath, $alt, $options);
}

function optimizedHeroImage($imagePath, $alt = '', $options = []) {
    return OptimizedImage::renderHeroImage($imagePath, $alt, $options);
}

function optimizedAvatar($imagePath, $alt = '', $options = []) {
    return OptimizedImage::renderAvatar($imagePath, $alt, $options);
}

function optimizedThumbnail($imagePath, $alt = '', $options = []) {
    return OptimizedImage::renderThumbnail($imagePath, $alt, $options);
}
