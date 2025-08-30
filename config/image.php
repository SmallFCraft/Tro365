<?php
/**
 * Image Configuration
 * Tro365 - Website thuê trọ
 */

return [
    // General optimization settings
    'enabled' => true,
    'quality' => 85,
    'max_width' => 1920,
    'max_height' => 1080,
    
    // Modern format support
    'webp' => [
        'enabled' => true,
        'quality' => 85,
    ],
    
    'avif' => [
        'enabled' => function_exists('imageavif'), // Auto-detect server support
        'quality' => 80, // AVIF can use lower quality for same visual result
    ],
    
    // Thumbnail settings
    'thumbnails' => [
        'posts' => [
            'enabled' => true,
            'width' => 300,
            'height' => 200,
            'create_webp' => true,
        ],
        'avatars' => [
            'enabled' => true,
            'width' => 150,
            'height' => 150,
            'create_webp' => true,
        ],
    ],
    
    // Upload type specific settings
    'upload_types' => [
        'posts' => [
            'max_width' => 1920,
            'max_height' => 1080,
            'quality' => 85,
            'create_thumbnails' => true,
        ],
        'avatars' => [
            'max_width' => 500,
            'max_height' => 500,
            'quality' => 90,
            'create_thumbnails' => true,
        ],
        'general' => [
            'max_width' => 1920,
            'max_height' => 1080,
            'quality' => 85,
            'create_thumbnails' => false,
        ],
    ],
    
    // Performance settings
    'async_processing' => false, // Set to true for background processing
    'batch_size' => 10, // For batch processing
    
    // Logging
    'log_optimization' => true,
    'log_level' => 'info', // info, warning, error
    
    // Fallback settings
    'fallback_to_basic_resize' => true,
    'use_spatie_optimizer' => true,
    
    // File size limits (in bytes)
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'min_file_size_for_optimization' => 50 * 1024, // 50KB
    
    // Supported formats
    'supported_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    
    // Auto-cleanup old formats
    'cleanup' => [
        'remove_original_after_optimization' => false,
        'cleanup_failed_conversions' => true,
    ],
];
