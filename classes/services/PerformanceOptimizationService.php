<?php

namespace Tro365\Services;

/**
 * Performance Optimization Service (Dynamic Layer)
 *
 * ARCHITECTURE RESPONSIBILITIES:
 * - Advanced caching with Symfony FilesystemAdapter
 * - Performance monitoring and metrics collection
 * - Image optimization integration
 * - Database query optimization
 * - Cache warming and invalidation strategies
 *
 * WORKS WITH: PerformanceOptimization (Static Layer)
 * - Static layer handles: HTTP headers, gzip, basic DNS prefetch
 * - No conflicts: Coordinated to avoid duplicate headers
 *
 * Manages LCP optimization, conditional loading, performance monitoring, and cache integration
 */
class PerformanceOptimizationService
{
    private static $instance = null;
    private $currentPage;
    private $optimizations = [];

    // Cache integration properties
    private $cacheMetrics = [];
    private $cacheStartTime;
    private $enableCacheWarming = true; // Re-enabled with intelligent warming
    private $cacheThresholds = [
        'hit_ratio_warning' => 0.7,  // Warn if cache hit ratio < 70%
        'hit_ratio_critical' => 0.5, // Critical if cache hit ratio < 50%
        'execution_time_cache_invalidate' => 5000 // Invalidate cache if execution > 5s
    ];

    // Image optimization properties
    private $imageQuality = 85;
    private $maxImageWidth = 1920;
    private $maxImageHeight = 1080;
    private $enableWebP = true;
    private $enableAVIF = false;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->currentPage = $_SERVER['REQUEST_URI'] ?? '/';
        $this->cacheStartTime = microtime(true);
        $this->initializeCacheMetrics();
        $this->detectOptimizations();
        $this->initializeImageOptimization();

        // Cache warming is now enabled with intelligent warming for better performance
        // All cache integration features are available for monitoring
    }

    /**
     * Initialize cache metrics tracking (minimal overhead)
     */
    private function initializeCacheMetrics(): void
    {
        $this->cacheMetrics = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'invalidations' => 0,
            'warming_operations' => 0,
            'start_time' => $this->cacheStartTime
        ];

        // Cache tracking disabled for performance - metrics available on demand only
    }

    /**
     * Perform cache warming asynchronously (called on shutdown)
     */
    public function performCacheWarmingAsync(): void
    {
        if (!$this->enableCacheWarming || !function_exists('cache_get')) {
            return;
        }

        // Only warm cache if execution time was reasonable
        $executionTime = (microtime(true) - $this->cacheStartTime) * 1000;
        if ($executionTime > 100) { // Skip warming if page already slow
            return;
        }

        // Warm cache based on current page type (non-blocking)
        try {
            if ($this->isHomepage()) {
                $this->warmHomepageCacheLazy();
            } elseif ($this->isSearchPage()) {
                $this->warmSearchCacheLazy();
            }
        } catch (\Exception $e) {
            // Silent fail for cache warming
            error_log("Cache warming failed: " . $e->getMessage());
        }
    }

    /**
     * Legacy method for immediate cache warming (now disabled)
     */
    private function performCacheWarming(): void
    {
        // Disabled to improve page load performance
        // Cache warming now happens asynchronously via shutdown function
        return;
    }

    /**
     * Warm homepage cache with critical data (lazy version)
     */
    private function warmHomepageCacheLazy(): void
    {
        $warmed = 0;

        // Only warm if cache is completely empty (avoid unnecessary work)
        if (cache_get('search:categories') === null) {
            $this->warmCacheKeyLazy('search:categories', function() {
                global $db;
                if (!$db) return [];

                // Quick query with limit to avoid heavy operations
                return $db->select("SELECT * FROM DanhMuc WHERE TrangThai = 1 ORDER BY ThuTu, TenDM LIMIT 10");
            }, 600);
            $warmed++;
        }

        $this->cacheMetrics['warming_operations'] += $warmed;
    }

    /**
     * Warm homepage cache with critical data (original - now unused)
     */
    private function warmHomepageCache(): void
    {
        // Disabled for performance - use lazy version instead
        return;
    }

    /**
     * Warm search page cache (lazy version)
     */
    private function warmSearchCacheLazy(): void
    {
        $warmed = 0;

        // Only warm one popular search to avoid overhead
        $cacheKey = 'search:popular:phong-tro';
        if (cache_get($cacheKey) === null) {
            $this->warmCacheKeyLazy($cacheKey, function() {
                return ['warmed' => true, 'search' => 'phong-tro', 'timestamp' => time()];
            }, 300);
            $warmed++;
        }

        $this->cacheMetrics['warming_operations'] += $warmed;
    }

    /**
     * Warm search page cache (original - now unused)
     */
    private function warmSearchCache(): void
    {
        // Disabled for performance - use lazy version instead
        return;
    }

    /**
     * Warm specific cache key with data (lazy version - minimal overhead)
     */
    private function warmCacheKeyLazy(string $key, callable $dataProvider, int $ttl = 3600): void
    {
        try {
            // Set timeout to prevent hanging
            $startTime = microtime(true);
            $data = $dataProvider();
            $duration = (microtime(true) - $startTime) * 1000;

            // Only cache if operation was fast (< 50ms)
            if ($duration < 50 && $data !== null && function_exists('cache_set')) {
                cache_set($key, $data, $ttl);
            }
        } catch (\Exception $e) {
            // Silent fail for lazy warming
        }
    }

    /**
     * Warm specific cache key with data (original - now unused)
     */
    private function warmCacheKey(string $key, callable $dataProvider, int $ttl = 3600): void
    {
        // Disabled for performance - use lazy version instead
        return;
    }

    /**
     * Initialize image optimization settings
     */
    private function initializeImageOptimization(): void
    {
        $this->imageQuality = defined('IMAGE_QUALITY') ? IMAGE_QUALITY : 85;
        $this->maxImageWidth = defined('MAX_IMAGE_WIDTH') ? MAX_IMAGE_WIDTH : 1920;
        $this->maxImageHeight = defined('MAX_IMAGE_HEIGHT') ? MAX_IMAGE_HEIGHT : 1080;
        $this->enableWebP = function_exists('imagewebp');
        $this->enableAVIF = function_exists('imageavif');
    }
    
    /**
     * Detect which optimizations are needed for current page
     */
    private function detectOptimizations(): void
    {
        // Homepage optimizations
        if ($this->isHomepage()) {
            $this->optimizations[] = 'lcp_hero';
            $this->optimizations[] = 'critical_css';
            $this->optimizations[] = 'performance_observer';
        }
        
        // Search page optimizations
        if ($this->isSearchPage()) {
            $this->optimizations[] = 'performance_observer';
            $this->optimizations[] = 'lazy_loading';
        }
        
        // Post detail optimizations
        if ($this->isPostPage()) {
            $this->optimizations[] = 'image_optimization';
            $this->optimizations[] = 'lazy_loading';
        }
    }
    
    /**
     * Check if current page is homepage
     */
    public function isHomepage(): bool
    {
        return $this->currentPage === '/' || str_starts_with($this->currentPage, '/?');
    }
    
    /**
     * Check if current page is search page
     */
    public function isSearchPage(): bool
    {
        return str_starts_with($this->currentPage, '/search');
    }
    
    /**
     * Check if current page is post detail page
     */
    public function isPostPage(): bool
    {
        return preg_match('/^\/post\/\d+/', $this->currentPage);
    }
    
    /**
     * Get LCP optimization for homepage
     */
    public function getLCPOptimization(): array
    {
        if (!in_array('lcp_hero', $this->optimizations)) {
            return [];
        }
        
        return [
            'preload_hero_image' => '/assets/images/hero_section.jpg',
            'critical_css' => $this->getCriticalHeroCSS(),
            'fetchpriority' => 'high'
        ];
    }
    
    /**
     * Get critical CSS for hero section
     */
    public function getCriticalHeroCSS(): string
    {
        return '
        <style>
        /* Critical Hero CSS - Inline for LCP optimization */
        .hero-section {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: url(\'/assets/images/hero_section.jpg\') center center/cover no-repeat;
            color: white;
            background-attachment: scroll;
        }
        .hero-section::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.6) 100%);
            z-index: 0;
        }
        .hero-content { position: relative; z-index: 1; }
        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, rgba(255, 255, 255, 0.8) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            line-height: 1.2;
        }
        @media (max-width: 768px) {
            .hero-section { 
                min-height: 70vh; 
                background-attachment: scroll !important;
            }
        }
        </style>';
    }
    
    /**
     * Check if performance observer should be loaded
     */
    public function shouldLoadPerformanceObserver(): bool
    {
        return in_array('performance_observer', $this->optimizations);
    }
    
    /**
     * Get conditional JavaScript files
     */
    public function getConditionalJS(): array
    {
        $js = [];
        
        if ($this->shouldLoadPerformanceObserver()) {
            $js[] = '/assets/js/global/performance-observer.js';
        }
        
        if (in_array('lazy_loading', $this->optimizations)) {
            // Lazy loading is already loaded globally
        }
        
        return $js;
    }
    
    /**
     * Get performance metrics for current page (enhanced with cache data)
     */
    public function getPerformanceMetrics(): array
    {
        $startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $currentTime = microtime(true);
        $executionTime = ($currentTime - $startTime) * 1000;

        // Calculate cache metrics
        $cacheMetrics = $this->calculateCacheMetrics();

        return [
            'execution_time' => $executionTime,
            'memory_usage' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage(),
            'optimizations_applied' => $this->optimizations,
            'lcp_optimized' => in_array('lcp_hero', $this->optimizations),
            'conditional_loading' => $this->shouldLoadPerformanceObserver(),

            // Enhanced cache metrics
            'cache_enabled' => function_exists('cache_get') && defined('CACHE_ENABLED') && CACHE_ENABLED,
            'cache_hit_ratio' => $cacheMetrics['hit_ratio'],
            'cache_operations' => $cacheMetrics['total_operations'],
            'cache_hits' => $cacheMetrics['hits'],
            'cache_misses' => $cacheMetrics['misses'],
            'cache_warming_operations' => $this->cacheMetrics['warming_operations'],
            'cache_performance_impact' => $cacheMetrics['performance_impact']
        ];
    }

    /**
     * Calculate cache performance metrics
     */
    private function calculateCacheMetrics(): array
    {
        $totalOps = $this->cacheMetrics['hits'] + $this->cacheMetrics['misses'];
        $hitRatio = $totalOps > 0 ? $this->cacheMetrics['hits'] / $totalOps : 0;

        // Estimate performance impact of cache
        $estimatedSavings = $this->cacheMetrics['hits'] * 10; // Assume 10ms saved per cache hit

        return [
            'hit_ratio' => round($hitRatio, 3),
            'total_operations' => $totalOps,
            'hits' => $this->cacheMetrics['hits'],
            'misses' => $this->cacheMetrics['misses'],
            'performance_impact' => $estimatedSavings . 'ms saved'
        ];
    }
    
    /**
     * Generate performance report
     */
    public function generatePerformanceReport(): array
    {
        $metrics = $this->getPerformanceMetrics();
        
        return [
            'page' => $this->currentPage,
            'timestamp' => date('Y-m-d H:i:s'),
            'metrics' => $metrics,
            'recommendations' => $this->getRecommendations($metrics),
            'lcp_target_met' => $metrics['execution_time'] < 2500 // 2.5s target
        ];
    }
    
    /**
     * Get performance recommendations (enhanced with cache analysis)
     */
    private function getRecommendations(array $metrics): array
    {
        $recommendations = [];

        // Execution time recommendations
        if ($metrics['execution_time'] > 2500) {
            if (!$metrics['cache_enabled']) {
                $recommendations[] = 'Enable caching system for significant performance improvement';
            } elseif ($metrics['cache_hit_ratio'] < $this->cacheThresholds['hit_ratio_warning']) {
                $recommendations[] = 'Cache hit ratio is low (' . ($metrics['cache_hit_ratio'] * 100) . '%) - consider cache warming or longer TTL';
            } else {
                $recommendations[] = 'Consider optimizing database queries and enabling more aggressive caching';
            }
        }

        // Cache-specific recommendations
        if ($metrics['cache_enabled']) {
            if ($metrics['cache_hit_ratio'] < $this->cacheThresholds['hit_ratio_critical']) {
                $recommendations[] = 'CRITICAL: Cache hit ratio is very low (' . ($metrics['cache_hit_ratio'] * 100) . '%) - investigate cache configuration';
                $this->triggerCacheInvalidation('low_hit_ratio');
            } elseif ($metrics['cache_hit_ratio'] < $this->cacheThresholds['hit_ratio_warning']) {
                $recommendations[] = 'Cache hit ratio could be improved (' . ($metrics['cache_hit_ratio'] * 100) . '%) - consider cache warming strategies';
            }

            if ($metrics['cache_operations'] === 0) {
                $recommendations[] = 'No cache operations detected - verify cache integration';
            }
        } else {
            $recommendations[] = 'Caching is disabled - enable for significant performance gains';
        }

        // Memory usage recommendations
        if ($metrics['memory_usage'] > 10 * 1024 * 1024) { // 10MB
            $recommendations[] = 'Consider reducing memory usage or implementing memory-efficient caching';
        }

        // LCP optimization
        if (!$metrics['lcp_optimized'] && $this->isHomepage()) {
            $recommendations[] = 'Enable LCP optimization for hero section';
        }

        // Performance threshold invalidation
        if ($metrics['execution_time'] > $this->cacheThresholds['execution_time_cache_invalidate']) {
            $recommendations[] = 'CRITICAL: Execution time exceeded threshold - triggering cache invalidation';
            $this->triggerCacheInvalidation('execution_time_exceeded');
        }

        return $recommendations;
    }

    /**
     * Trigger cache invalidation based on performance thresholds
     */
    private function triggerCacheInvalidation(string $reason): void
    {
        if (!function_exists('cache_get')) {
            return;
        }

        try {
            // Log the invalidation
            error_log("Performance-triggered cache invalidation: $reason at " . date('Y-m-d H:i:s'));

            // Invalidate specific cache keys based on reason
            switch ($reason) {
                case 'low_hit_ratio':
                    // Clear search-related cache that might be stale
                    $this->invalidateCachePattern('search:*');
                    break;

                case 'execution_time_exceeded':
                    // Clear all performance-sensitive cache
                    $this->invalidateCachePattern('search:*');
                    $this->invalidateCachePattern('profile:*');
                    break;
            }

            $this->cacheMetrics['invalidations']++;

        } catch (\Exception $e) {
            error_log("Cache invalidation failed: " . $e->getMessage());
        }
    }

    /**
     * Invalidate cache keys matching pattern
     */
    private function invalidateCachePattern(string $pattern): void
    {
        // Simple pattern matching for common cache keys
        $commonKeys = [
            'search:categories', 'search:posts:', 'search:popular:',
            'profile:total_posts:', 'profile:activities:',
            'locations:provinces', 'locations:districts:'
        ];

        $patternPrefix = str_replace('*', '', $pattern);

        foreach ($commonKeys as $key) {
            if (str_starts_with($key, $patternPrefix)) {
                // Try to invalidate if cache client supports it
                if (function_exists('cache_client')) {
                    $client = cache_client();
                    if ($client && method_exists($client, 'delete')) {
                        try {
                            $client->delete($key);
                        } catch (\Exception $e) {
                            // Silent fail for individual key deletion
                        }
                    }
                }
            }
        }
    }

    /**
     * Finalize cache metrics (called on shutdown) - Logging disabled for performance
     */
    public function finalizeCacheMetrics(): void
    {
        // Cache logging disabled to avoid verbose logs
        // Metrics are still available via getCacheStatistics() method
        return;
    }
    
    /**
     * Track cache hit for metrics
     */
    public function trackCacheHit(string $key): void
    {
        $this->cacheMetrics['hits']++;
    }

    /**
     * Track cache miss for metrics
     */
    public function trackCacheMiss(string $key): void
    {
        $this->cacheMetrics['misses']++;
    }

    /**
     * Track cache set operation
     */
    public function trackCacheSet(string $key): void
    {
        $this->cacheMetrics['sets']++;
    }

    /**
     * Get current cache statistics
     */
    public function getCacheStatistics(): array
    {
        $metrics = $this->calculateCacheMetrics();

        return [
            'enabled' => function_exists('cache_get') && defined('CACHE_ENABLED') && CACHE_ENABLED,
            'hit_ratio' => $metrics['hit_ratio'],
            'hit_ratio_percentage' => round($metrics['hit_ratio'] * 100, 1) . '%',
            'total_operations' => $metrics['total_operations'],
            'hits' => $metrics['hits'],
            'misses' => $metrics['misses'],
            'sets' => $this->cacheMetrics['sets'],
            'invalidations' => $this->cacheMetrics['invalidations'],
            'warming_operations' => $this->cacheMetrics['warming_operations'],
            'performance_impact' => $metrics['performance_impact'],
            'status' => $this->getCacheHealthStatus($metrics['hit_ratio'])
        ];
    }

    /**
     * Get cache health status based on hit ratio
     */
    private function getCacheHealthStatus(float $hitRatio): string
    {
        if ($hitRatio >= 0.8) {
            return 'excellent';
        } elseif ($hitRatio >= $this->cacheThresholds['hit_ratio_warning']) {
            return 'good';
        } elseif ($hitRatio >= $this->cacheThresholds['hit_ratio_critical']) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    /**
     * Apply HTTP headers for performance - Global system (enhanced with cache headers)
     * Note: Basic DNS prefetch headers are handled by PerformanceOptimization class
     */
    public function applyPerformanceHeaders(): void
    {
        // DNS prefetch headers are handled by PerformanceOptimization class to avoid duplicates
        // Only apply advanced service-specific headers here

        // Glass-morphism CSS loads naturally via header.php - no preload needed

        // Apply page-specific optimizations
        $this->applyPageSpecificHeaders();

        // Font preload removed - Google Fonts already handles Inter font loading optimally

        // Add cache performance headers for debugging
        if (defined('DEBUG') && DEBUG) {
            $cacheStats = $this->getCacheStatistics();
            header('X-Cache-Hit-Ratio: ' . $cacheStats['hit_ratio_percentage']);
            header('X-Cache-Status: ' . $cacheStats['status']);
            header('X-Cache-Operations: ' . $cacheStats['total_operations']);
        }
    }

    /**
     * Apply page-specific performance headers
     */
    private function applyPageSpecificHeaders(): void
    {
        if ($this->isHomepage()) {
            // Homepage LCP optimization
            header('Link: </assets/images/hero_section.jpg>; rel=preload; as=image; fetchpriority=high');
            header('Link: </assets/css/client/main.css>; rel=preload; as=style');
            // Removed preload of performance-observer.js; it will be conditionally loaded in footer if needed to avoid preload-not-used warning
            // header('Link: </assets/js/global/performance-observer.js>; rel=preload; as=script');

        } elseif ($this->isSearchPage()) {
            // Search page optimization
            // Removed preload of performance-observer.js; it will be conditionally loaded in footer if needed to avoid preload-not-used warning
            // header('Link: </assets/js/global/performance-observer.js>; rel=preload; as=script');
            header('Link: </assets/js/client/lazy-loading.js>; rel=preload; as=script');

        } elseif ($this->isPostPage()) {
            // Post detail optimization
            header('Link: </assets/js/client/lazy-loading.js>; rel=preload; as=script');
            header('Link: </assets/js/global/image-fallback.js>; rel=preload; as=script');

        } elseif ($this->isAdminPage()) {
            // Admin pages optimization - remove preload since CSS is already loaded in header
            // header('Link: </assets/css/admin/admin.css>; rel=preload; as=style');

        } elseif ($this->isAuthPage()) {
            // Auth pages - minimal resources for fast loading
            // No additional preloads needed
        }
    }

    /**
     * Check if current page is admin page
     */
    public function isAdminPage(): bool
    {
        return str_starts_with($this->currentPage, '/admin');
    }

    /**
     * Check if current page is auth page
     */
    public function isAuthPage(): bool
    {
        $authPages = ['/login', '/register', '/forgot-password', '/logout'];
        return in_array($this->currentPage, $authPages) ||
               in_array(parse_url($this->currentPage, PHP_URL_PATH), $authPages);
    }

    // ========================================
    // IMAGE OPTIMIZATION METHODS (Consolidated from ImageOptimizationService)
    // ========================================

    /**
     * Optimize uploaded image automatically
     * @param string $imagePath Path to image file
     * @param string $type Type of optimization (general, posts, avatar)
     * @return array Optimization results
     */
    public function optimizeUploadedImage($imagePath, $type = 'general'): array
    {
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
            if ($this->enableAVIF) {
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

            // Log optimization results
            $this->logImageOptimization($imagePath, $results);

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
            $this->logImageOptimization($imagePath, $results, $e->getMessage());
        }

        return $results;
    }

    /**
     * Check if file is a valid image
     */
    private function isImageFile($imagePath): bool
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Optimize original image (resize + compress)
     */
    private function optimizeOriginalImage($imagePath): void
    {
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
        $needsResize = ($currentWidth > $this->maxImageWidth || $currentHeight > $this->maxImageHeight);

        if ($needsResize) {
            $ratio = min($this->maxImageWidth / $currentWidth, $this->maxImageHeight / $currentHeight);
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
    private function createWebPVersion($imagePath)
    {
        $pathInfo = pathinfo($imagePath);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

        $extension = strtolower($pathInfo['extension']);
        $image = $this->loadImage($imagePath, $extension);

        if ($image && imagewebp($image, $webpPath, $this->imageQuality)) {
            imagedestroy($image);
            return $webpPath;
        }

        if ($image) imagedestroy($image);
        return false;
    }

    /**
     * Create AVIF version
     */
    private function createAVIFVersion($imagePath)
    {
        if (!function_exists('imageavif')) {
            return false;
        }

        $pathInfo = pathinfo($imagePath);
        $avifPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.avif';

        $extension = strtolower($pathInfo['extension']);
        $image = $this->loadImage($imagePath, $extension);

        if ($image && imageavif($image, $avifPath, $this->imageQuality)) {
            imagedestroy($image);
            return $avifPath;
        }

        if ($image) imagedestroy($image);
        return false;
    }

    /**
     * Create thumbnails for posts
     */
    private function createThumbnails($imagePath): void
    {
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
                imagewebp($thumbnail, $thumbWebpPath, $this->imageQuality);
            }

            imagedestroy($thumbnail);
            imagedestroy($image);
        }
    }

    /**
     * Load image based on extension
     */
    private function loadImage($imagePath, $extension)
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($imagePath);
            case 'png':
                // Suppress libpng warnings about incorrect sRGB profiles
                $image = @imagecreatefrompng($imagePath);
                if ($image === false) {
                    throw new \Exception("Failed to create PNG image from: $imagePath");
                }
                return $image;
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
    private function saveImage($image, $imagePath, $extension): bool
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $imagePath, $this->imageQuality);
            case 'png':
                return imagepng($image, $imagePath, (int)(9 - ($this->imageQuality / 100) * 9));
            case 'gif':
                return imagegif($image, $imagePath);
            case 'webp':
                return imagewebp($image, $imagePath, $this->imageQuality);
            default:
                return false;
        }
    }

    /**
     * Log image optimization results (simplified)
     */
    private function logImageOptimization($imagePath, $results, $error = null): void
    {
        $logFile = 'logs/image.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        if ($error) {
            $logMessage = "[ERROR] " . basename($imagePath) . ": $error";
        } else {
            $logMessage = "[OK] " . basename($imagePath) . " optimized";
        }

        $logMessage .= PHP_EOL;
        @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// ========================================
// BACKWARD COMPATIBILITY WRAPPER
// ========================================

/**
 * Backward compatibility wrapper for ImageOptimizationService
 * @deprecated Use PerformanceOptimizationService instead
 */
class_alias('Tro365\Services\PerformanceOptimizationService', 'Tro365\Services\ImageOptimizationService');
