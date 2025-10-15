<?php
/**
 * Performance Optimization Headers (Static Layer)
 *
 * ARCHITECTURE RESPONSIBILITIES:
 * - HTTP headers (gzip, cache, security)
 * - Static asset optimization
 * - Basic DNS prefetch
 * - Page-specific preload hints
 *
 * WORKS WITH: PerformanceOptimizationService (Dynamic Layer)
 * - Service handles: Advanced caching, monitoring, image optimization
 * - No conflicts: Headers are coordinated to avoid duplicates
 *
 * Reduces document request latency and improves PageSpeed scores
 */

class PerformanceOptimization {
    
    /**
     * Apply performance headers
     */
    public static function applyHeaders() {
        // Enable gzip compression
        if (!ob_get_level() && extension_loaded('zlib') && !headers_sent()) {
            ob_start('ob_gzhandler');
        }
        
        // Set caching headers for static assets
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (self::isStaticAsset($requestUri)) {
            self::setStaticAssetHeaders();
        } else {
            self::setDynamicContentHeaders();
        }
        
        // Security headers
        self::setSecurityHeaders();
        
        // Performance hints
        self::setPerformanceHints();
    }
    
    /**
     * Check if request is for static asset
     */
    private static function isStaticAsset($uri) {
        $staticExtensions = ['.css', '.js', '.jpg', '.jpeg', '.png', '.gif', '.webp', '.avif', '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot'];
        
        foreach ($staticExtensions as $ext) {
            if (str_ends_with($uri, $ext)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Set headers for static assets
     */
    private static function setStaticAssetHeaders() {
        // Long-term caching for static assets
        header('Cache-Control: public, max-age=31536000, immutable'); // 1 year
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        
        // ETag for cache validation
        $etag = md5($_SERVER['REQUEST_URI'] . filemtime($_SERVER['SCRIPT_FILENAME']));
        header('ETag: "' . $etag . '"');
        
        // Check if client has cached version
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
            http_response_code(304);
            exit;
        }
    }
    
    /**
     * Set headers for dynamic content
     */
    private static function setDynamicContentHeaders() {
        // Short-term caching for dynamic content
        header('Cache-Control: public, max-age=300, must-revalidate'); // 5 minutes
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
        
        // Vary header for different content based on user agent
        header('Vary: Accept-Encoding, User-Agent');
    }
    
    /**
     * Set security headers
     */
    private static function setSecurityHeaders() {
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (basic)
        header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' data: https:; img-src 'self' data: https: blob:; font-src 'self' data: https:;");
    }
    
    /**
     * Set performance hints
     */
    private static function setPerformanceHints() {
        // DNS prefetch for external domains
        header('Link: <//fonts.googleapis.com>; rel=dns-prefetch');
        header('Link: <//cdnjs.cloudflare.com>; rel=dns-prefetch');

        // Get current page info first
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $pageType = self::detectPageType($uri);

        // Glass-morphism CSS loads naturally via header.php - no preload needed

        // Apply page-specific optimizations
        self::applyPageOptimizations($pageType, $uri);
    }

    /**
     * Detect page type for optimization
     */
    private static function detectPageType($uri) {
        if ($uri === '/' || str_starts_with($uri, '/?')) {
            return 'homepage';
        } elseif (str_starts_with($uri, '/search')) {
            return 'search';
        } elseif (preg_match('/^\/post\/\d+/', $uri)) {
            return 'post_detail';
        } elseif (str_starts_with($uri, '/admin')) {
            return 'admin';
        } elseif (in_array($uri, ['/login', '/register', '/forgot-password'])) {
            return 'auth';
        } else {
            return 'general';
        }
    }

    /**
     * Apply page-specific optimizations
     */
    private static function applyPageOptimizations($pageType, $uri) {
        switch ($pageType) {
            case 'homepage':
                // Homepage LCP optimization
                header('Link: </assets/images/hero_section.jpg>; rel=preload; as=image; fetchpriority=high');
                header('Link: </assets/css/client/main.css>; rel=preload; as=style');
                // Removed preload of performance-observer.js to avoid 'preloaded but not used' warnings; script is loaded conditionally via footer when needed
                // header('Link: </assets/js/global/performance-observer.js>; rel=preload; as=script');
                break;

            case 'search':
                // Search page optimization
                // Removed preload of performance-observer.js to avoid 'preloaded but not used' warnings; script is loaded conditionally via footer when needed
                // header('Link: </assets/js/global/performance-observer.js>; rel=preload; as=script');
                // Removed lazy-loading.js preload to avoid 'preloaded but not used' warnings; script is loaded with defer in header
                // header('Link: </assets/js/client/lazy-loading.js>; rel=preload; as=script');
                break;

            case 'post_detail':
                // Post detail optimization
                header('Link: </assets/js/client/lazy-loading.js>; rel=preload; as=script');
                header('Link: </assets/js/global/image-fallback.js>; rel=preload; as=script');
                break;

            case 'admin':
                // Admin pages optimization - remove preload since CSS is already loaded in header
                // header('Link: </assets/css/admin/admin.css>; rel=preload; as=style');
                break;

            case 'auth':
                // Auth pages optimization - minimal resources
                break;

            default:
                // General pages - basic optimization
                break;
        }

        // Font preload removed - Google Fonts already handles Inter font loading optimally
    }
    
    // Image optimization methods removed - use PerformanceOptimizationService instead
    // All image optimization is now handled by the unified PerformanceOptimizationService
    // and helper functions getOptimizedImageUrl() and generateResponsiveSrcset()
}

// ========================================
// OPTIMIZED IMAGE COMPONENT (Consolidated from OptimizedImage.php)
// ========================================

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
     * DEPRECATED: Unused methods removed to reduce code bloat
     * The website uses generateImageHtml(), getUserAvatarHtml(),
     * getOptimizedImageUrl(), and generateResponsiveSrcset() instead
     *
     * If you need these methods, use the existing helper functions:
     * - generateImageHtml() for general images
     * - getUserAvatarHtml() for avatars
     * - getOptimizedImageUrl() for optimized URLs
     * - generateResponsiveSrcset() for responsive images
     */
}

/**
 * DEPRECATED: Helper functions removed - use existing functions instead
 *
 * Use these existing functions instead:
 * - generateImageHtml($imagePath, $alt, $class, $attributes) for general images
 * - getUserAvatarHtml($avatarPath, $cssClass, $alt, $style) for avatars
 * - getOptimizedImageUrl($imagePath, $width, $height) for optimized URLs
 * - generateResponsiveSrcset($imagePath, $sizes) for responsive images
 */

// Apply performance optimizations automatically
if (!defined('SKIP_PERFORMANCE_HEADERS')) {
    PerformanceOptimization::applyHeaders();

    // Apply advanced performance optimizations if service is available
    if (class_exists('Tro365\Services\PerformanceOptimizationService')) {
        try {
            $perfService = \Tro365\Services\PerformanceOptimizationService::getInstance();
            $perfService->applyPerformanceHeaders();
        } catch (Exception $e) {
            // Fallback gracefully if service fails
            error_log("PerformanceOptimizationService failed: " . $e->getMessage());
        }
    }
}
?>
