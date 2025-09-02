<?php
/**
 * Main Entry Point
 * Tro365 - Website thuê trọ
 */

// Start output buffering
ob_start();

// Load configuration and dependencies
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/constants.php';

// Apply performance optimizations
require_once __DIR__ . '/includes/performance/optimization.php';

// Load helper functions
require_once __DIR__ . '/includes/functions/helpers.php';
require_once __DIR__ . '/includes/functions/auth.php';
require_once __DIR__ . '/includes/functions/validation.php';
require_once __DIR__ . '/includes/middleware/maintenance.php';
use Tro365\Core\Database;


// Sanitize route segments for fallback file resolution
function sanitize_route_segment($s) {
    $s = (string) $s;
    if ($s === '') return '';
    // allow only letters, numbers, slash, underscore, hyphen
    $s = preg_replace('/[^a-z0-9\/_-]/i', '', $s);
    // remove any directory traversal patterns
    $s = str_replace('..', '', $s);
    // trim leading/trailing slashes
    return trim($s, '/');
}

try {
    // Initialize database connection
    $db = Database::getInstance();

    // Check maintenance mode
    checkMaintenanceMode();

    // Get route from URL
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $route = trim($requestUri, '/');

    // Remove query string
    if (strpos($route, '?') !== false) {
        $route = substr($route, 0, strpos($route, '?'));
    }

    // Default route
    if (empty($route)) {
        $route = 'home';
    }

    // Debug: Log the route
    if (APP_DEBUG) {
        error_log("Route: " . $route . " (from URI: " . $requestUri . ")");
    }

    // Special handling for SEO files
    if ($route === 'robots.txt' || $route === 'sitemap.xml') {
        $filePath = ($route === 'robots.txt') ? 'robots.php' : 'sitemap.php';
        if (file_exists($filePath)) {
            include $filePath;
            exit;
        }
    }

    // Parse route
    $routeParts = explode('/', $route);
    $controller = $routeParts[0] ?? 'home';
    $action = $routeParts[1] ?? 'index';
    $id = $routeParts[2] ?? null;

    // Special handling for API routes
    if ($controller === 'router' && $action === 'api') {
        // Handle router/api/* routes directly
        $apiRoute = implode('/', array_slice($routeParts, 2));
        $route = 'api/' . $apiRoute;
    }

    // Special handling for post detail URLs like /post/123
    if ($controller === 'post' && is_numeric($action)) {
        $id = $action;
        $action = 'detail';
    }

    // Special handling for seller post edit URLs like /seller/posts/edit/123
    if ($controller === 'seller' && $action === 'posts' && isset($routeParts[2]) && $routeParts[2] === 'edit' && isset($routeParts[3]) && is_numeric($routeParts[3])) {
        $id = $routeParts[3];
        $route = 'seller/posts/edit';
    }

    // Route mapping
    $routes = [
        // Public routes
        'home' => 'pages/client/home.php',
        'search' => 'pages/client/search.php',
        'post' => 'pages/client/post-detail.php',
        'contact' => 'pages/client/contact.php',
        'about' => 'pages/client/about.php',

        // Auth routes
        'login' => 'pages/auth/login.php',
        'register' => 'pages/auth/register.php',
        'register-seller' => 'pages/seller/register-seller.php',
        'logout' => 'pages/auth/logout.php',
        'forgot-password' => 'pages/auth/forgot-password.php',
        'reset-password' => 'pages/auth/reset-password.php',
        'verify-email' => 'pages/auth/verify-email.php',
        'resend-verification' => 'pages/auth/resend-verification.php',

        // User profile routes
        'profile' => 'pages/client/profile/index.php',
        'profile/edit' => 'pages/client/profile/edit.php',
        'profile/favorites' => 'pages/client/profile/favorites.php',
        'profile/change-password' => 'pages/client/profile/change-password.php',
        'profile/history' => 'pages/client/profile/history.php',

        // Notifications routes
        'notifications' => 'pages/client/notifications/index.php',

        // Seller routes
        'seller' => 'pages/seller/dashboard.php',
        'seller/posts' => 'pages/seller/posts/index.php',
        'seller/posts/create' => 'pages/seller/posts/create.php',
        'seller/posts/edit' => 'pages/seller/posts/edit.php',
        'seller/contacts' => 'pages/seller/contacts.php',
        'seller/transactions' => 'pages/seller/transactions.php',
        'seller/stats' => 'pages/seller/stats.php',
        'register-seller' => 'pages/seller/register-seller.php',

        // Admin routes
        'admin' => 'pages/admin/dashboard.php',
        'admin/users' => 'pages/admin/users/index.php',
        'admin/users/create' => 'pages/admin/users/create.php',
        'admin/users/edit' => 'pages/admin/users/edit.php',
        'admin/posts' => 'pages/admin/posts/index.php',
        'admin/posts/approve' => 'pages/admin/posts/approve.php',
        'admin/sellers' => 'pages/admin/sellers/index.php',
        'admin/sellers/approve' => 'pages/admin/sellers/approve.php',
        'admin/sellers/pending-ajax' => 'pages/admin/sellers/pending-ajax.php',
        'admin/sellers/approve-ajax' => 'pages/admin/sellers/approve-ajax.php',
        'admin/sellers/reject-ajax' => 'pages/admin/sellers/reject-ajax.php',
        'admin/categories' => 'pages/admin/categories/index.php',
        'admin/categories/create' => 'pages/admin/categories/create.php',
        'admin/categories/edit' => 'pages/admin/categories/edit.php',
        'admin/users/info' => 'pages/admin/users/info.php',
        'admin/locations' => 'pages/admin/locations.php',
        'admin/transactions' => 'pages/admin/transactions/index.php',
        'admin/settings' => 'pages/admin/settings.php',
        'admin/ajax/settings-handler' => 'pages/admin/ajax/settings-handler.php',
        'admin/statistics' => 'pages/admin/statistics.php',
        'admin/cache/clear' => 'router/admin/cache-clear.php',
        
        // API routes
        'api/auth' => 'router/api/auth.php',
        'api/auth/refresh-session' => 'router/api/auth.php',
        'api/auth/current-user' => 'router/api/auth.php',
        'api/posts' => 'router/api/posts.php',
        'api/posts/list' => 'router/api/posts.php',
        'api/posts/get' => 'router/api/posts.php',
        'api/posts/create' => 'router/api/posts.php',
        'api/posts/update' => 'router/api/posts.php',
        'api/posts/delete' => 'router/api/posts.php',
        'api/posts/suggestions' => 'router/api/posts.php',
        'api/posts/remove-image' => 'router/api/posts.php',
        'api/users' => 'router/api/users.php',
        'api/locations/districts' => 'router/api/locations.php',
        'api/locations/wards' => 'router/api/locations.php',
        'api/locations/provinces' => 'router/api/locations.php',
        'api/locations/reverse-geocode' => 'router/api/locations.php',
        'api/upload' => 'router/api/upload.php',
        'api/check-availability' => 'router/api/check-availability.php',
        'api/notifications' => 'router/api/notifications.php',
        'api/toggle-favorite' => 'router/api/favorites.php',
        'api/favorites' => 'router/api/favorites.php',
        'api/favorites/toggle' => 'router/api/favorites.php',
        'api/check-favorite' => 'router/api/favorites.php',

        // Static pages
        'help' => 'pages/client/help.php',
        'terms' => 'pages/client/terms.php',
        'privacy' => 'pages/client/privacy.php',
        'sitemap' => 'pages/client/sitemap.php',
        'rss' => 'pages/client/rss.php',

        // SEO files
        'robots.txt' => 'robots.php',
        'sitemap.xml' => 'sitemap.php',

        // Error pages
        'error/403' => 'pages/errors/403.php',
        'error/404' => 'pages/errors/404.php',
        'error/500' => 'pages/errors/500.php',
    ];

    // Check if route exists
    $filePath = null;

    // Try exact match first
    if (isset($routes[$route])) {
        $filePath = $routes[$route];
    } else {
        // Try controller/action pattern
        $controllerAction = $controller . '/' . $action;
        if (isset($routes[$controllerAction])) {
            $filePath = $routes[$controllerAction];
        } else if (isset($routes[$controller])) {
            $filePath = $routes[$controller];
        }
    }

    // If no route found, try to find file directly with sanitized segments
    if (!$filePath) {
        $safeController = sanitize_route_segment($controller);
        $safeAction = sanitize_route_segment($action);

        if ($safeController !== '' && strpos($safeController, '..') === false && strpos($safeAction, '..') === false) {
            $possiblePaths = [
                "pages/client/{$safeController}.php",
                "pages/{$safeController}/{$safeAction}.php",
                "pages/{$safeController}.php"
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $dirReal = realpath(dirname($path)) ?: '';
                    $pagesBase = realpath('pages') ?: '';
                    if ($pagesBase && $dirReal && strpos($dirReal, $pagesBase) === 0) {
                        $filePath = $path;
                        break;
                    }
                }
            }
        }
    }

    // Load the page
    if ($filePath && file_exists($filePath)) {
        // Set current route info for use in pages
        $_REQUEST['current_controller'] = $controller;
        $_REQUEST['current_action'] = $action;
        $_REQUEST['current_id'] = $id;

        include $filePath;
    } else {
        // 404 Not Found
        http_response_code(404);
        include 'pages/errors/404.php';
    }

} catch (Exception $e) {
    // Log error
    error_log("Application Error: " . $e->getMessage());

    // Show error page
    if (APP_DEBUG) {
        echo "<h1>Application Error</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        http_response_code(500);
        include 'pages/errors/500.php';
    }
}

// End output buffering and send output
ob_end_flush();
?>
