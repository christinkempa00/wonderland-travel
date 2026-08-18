<?php
// Simple router for PHP built-in server to forward requests to index.php
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;

    // Serve the requested resource if it exists (css, js, images, etc.)
    if ($path !== '/' && is_file($file)) {
        return false;
    }

    // Rewrite: set _url query used by index.php router
    $_GET['_url'] = ltrim($path, '/');
}

require __DIR__ . '/index.php';
