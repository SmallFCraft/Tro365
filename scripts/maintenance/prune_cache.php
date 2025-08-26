<?php
// Prune cache script for Tro365
// - Deletes files older than a retention window in var/cache
// Usage: php scripts/maintenance/prune_cache.php [--force]
// Options:
//   --force : Force delete all cache files regardless of age

$root = dirname(__DIR__, 2);
$varCache = $root . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache';
$legacyCache = $root . DIRECTORY_SEPARATOR . 'cache';

// Check for force flag
$forceMode = in_array('--force', $argv);
$retentionSeconds = $forceMode ? 0 : (24 * 3600); // Force mode = delete all, normal = 24h
$now = time();
$deleted = 0;
$errors = 0;

echo "=== TRO365 CACHE PRUNING ===\n";
echo "Mode: " . ($forceMode ? "FORCE (delete all)" : "NORMAL (24h retention)") . "\n";
echo "Var Cache: $varCache\n";
echo "Legacy Cache: $legacyCache\n\n";

function pruneDir($dir, $retentionSeconds, $now, &$deleted, &$errors, $verbose = true) {
    if (!is_dir($dir)) {
        if ($verbose) echo "Directory not found: $dir\n";
        return;
    }

    if ($verbose) echo "Processing directory: $dir\n";

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($it as $fileinfo) {
        try {
            if ($fileinfo->isFile()) {
                $age = $now - $fileinfo->getMTime();
                if ($age > $retentionSeconds) {
                    if ($verbose) {
                        $ageHours = round($age / 3600, 1);
                        echo "Deleting file (age: {$ageHours}h): " . basename($fileinfo->getPathname()) . "\n";
                    }
                    if (@unlink($fileinfo->getPathname())) {
                        $deleted++;
                    } else {
                        if ($verbose) echo "Failed to delete: " . basename($fileinfo->getPathname()) . "\n";
                        $errors++;
                    }
                }
            } elseif ($fileinfo->isDir()) {
                // Remove empty directories
                @rmdir($fileinfo->getPathname());
            }
        } catch (Throwable $e) {
            if ($verbose) echo "Error: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

// Ensure var/cache exists
if (!is_dir($varCache)) @mkdir($varCache, 0775, true);

// 1) Prune var/cache by retention window
pruneDir($varCache, $retentionSeconds, $now, $deleted, $errors);

// 2) Remove legacy cache dir completely (one-time cleanup)
if (is_dir($legacyCache)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($legacyCache, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $fileinfo) {
        try {
            if ($fileinfo->isFile()) @unlink($fileinfo->getPathname());
            else @rmdir($fileinfo->getPathname());
        } catch (Throwable $e) { $errors++; }
    }
    @rmdir($legacyCache);
}

echo "\n=== RESULTS ===\n";
echo "Deleted files: {$deleted}\n";
echo "Errors: {$errors}\n";
echo "Cache pruning complete!\n";

