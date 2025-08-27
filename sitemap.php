<?php
/**
 * Dynamic Sitemap Generator
 * Tro365 - Website thuê trọ
 */

// Simple sitemap without complex dependencies
$baseUrl = 'http://localhost:8000';

// Set content type
header('Content-Type: application/xml; charset=utf-8');

try {

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    // Homepage
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}</loc>\n";
    echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    echo "    <changefreq>daily</changefreq>\n";
    echo "    <priority>1.0</priority>\n";
    echo "  </url>\n";
    
    // Static pages
    $staticPages = [
        'about' => ['changefreq' => 'monthly', 'priority' => '0.8'],
        'contact' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        'search' => ['changefreq' => 'daily', 'priority' => '0.9'],
    ];
    
    foreach ($staticPages as $page => $config) {
        echo "  <url>\n";
        echo "    <loc>" . rtrim($baseUrl, '/') . "/{$page}</loc>\n";
        echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        echo "    <changefreq>{$config['changefreq']}</changefreq>\n";
        echo "    <priority>{$config['priority']}</priority>\n";
        echo "  </url>\n";
    }
    
    // Static posts for now - can be enhanced later
    $staticPosts = [
        ['id' => 1, 'title' => 'Nha-Tro-70-Kenh-Nuoc-Den-Binh-Hung-Hoa-A'],
        ['id' => 2, 'title' => 'Nha-tro-so-73-Pham-Su-Manh-Phuong-Khue-Trung']
    ];

    foreach ($staticPosts as $post) {
        echo "  <url>\n";
        echo "    <loc>" . rtrim($baseUrl, '/') . "/post/{$post['id']}</loc>\n";
        echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.6</priority>\n";
        echo "  </url>\n";
    }
    
    echo '</urlset>';
    
} catch (Exception $e) {
    http_response_code(500);
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<error>Unable to generate sitemap</error>';
}

/**
 * Create URL-friendly slug
 */
function createSlug($text) {
    // Convert to lowercase
    $text = mb_strtolower($text, 'UTF-8');
    
    // Replace Vietnamese characters
    $vietnamese = [
        'à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
        'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
        'ì', 'í', 'ị', 'ỉ', 'ĩ',
        'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
        'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
        'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ',
        'đ'
    ];
    
    $ascii = [
        'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y',
        'd'
    ];
    
    $text = str_replace($vietnamese, $ascii, $text);
    
    // Remove special characters
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Replace spaces and multiple hyphens with single hyphen
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Trim hyphens from beginning and end
    return trim($text, '-');
}
?>
