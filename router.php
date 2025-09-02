<?php
/**
 * PHP Built-in Server Router
 * - Serves existing files directly
 * - Routes all other requests through index.php using ?route=
 * - Keeps original REQUEST_URI for app logic
 */

// Absolute path to project root
$root = __DIR__;

// Current URI and path
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';

// Optional debug (commented out)
// error_log("Router.php: URI=$uri PATH=$path");

// Normalize directory index (e.g., /admin/ -> /admin)
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

// If it's a real file under project root, let PHP serve it
$absolute = realpath($root . $path);
if ($absolute && is_file($absolute) && strpos($absolute, $root) === 0) {
    return false; // serve static file
}

// Otherwise, map to index.php via ?route=
$_GET['route'] = trim($path, '/');
// Ensure superglobals align with how index.php expects
$_SERVER['REQUEST_URI'] = $uri; // keep original for logging/logic

require $root . '/index.php';

