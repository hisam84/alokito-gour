<?php
/**
 * Local Development Server Router for PHP Built-in Server
 * Usage: php -c php.ini -S localhost:8000 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static assets directly if they exist
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// 1. Single Article: /news/{id}/{slug} -> single.php?id=$1&slug=$2
if (preg_match('#^/news/([0-9]+)(?:/([^/]+))?/?$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    if (isset($matches[2])) {
        $_GET['slug'] = $matches[2];
    }
    require __DIR__ . '/single.php';
    exit;
}

// 2. Category Archive: /category/{slug} -> category.php?slug=$1
if (preg_match('#^/category/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/category.php';
    exit;
}

// 3. Search: /search -> search.php
if ($uri === '/search' || $uri === '/search/') {
    require __DIR__ . '/search.php';
    exit;
}

// 4. Custom Page Builder Page: /page/{slug} -> page.php?slug=$1
if (preg_match('#^/page/([^/]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/page.php';
    exit;
}

// 5. Default to index.php
require __DIR__ . '/index.php';
