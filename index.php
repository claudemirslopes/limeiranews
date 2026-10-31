<?php

declare(strict_types=1);

/**
 * XAMPP front controller for http://localhost/limeiranews.
 * Laravel handles the API and SEO feeds. Any other URL is the Angular portal.
 */

$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$base = '/limeiranews';
$path = str_starts_with($uri, $base) ? (substr($uri, strlen($base)) ?: '/') : $uri;
$path = '/'.ltrim($path, '/');

$isLaravel = (bool) preg_match('#^/(api|sanctum|up)(/|$)#', $path)
    || in_array($path, ['/robots.txt', '/sitemap.xml', '/news-sitemap.xml', '/feed.xml'], true);

if (! $isLaravel) {
    if (str_starts_with($path, '/storage/')) {
        serveStorage(substr($path, strlen('/storage/')));
    }

    servePortal($path);
}

$public = __DIR__.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'public';
chdir($public);
require $public.DIRECTORY_SEPARATOR.'index.php';

function serveStorage(string $relative): never
{
    $root = realpath(__DIR__.DIRECTORY_SEPARATOR.'backend'.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public');
    $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    $target = $root ? realpath($root.DIRECTORY_SEPARATOR.$relative) : false;

    if (! $root || ! $target || ! str_starts_with($target, $root) || ! is_file($target)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Arquivo nao encontrado.';
        exit;
    }

    header('Content-Type: '.fileMime($target));
    readfile($target);
    exit;
}

function servePortal(string $path): never
{
    $browser = __DIR__.DIRECTORY_SEPARATOR.'dist'.DIRECTORY_SEPARATOR.'limeiranews'.DIRECTORY_SEPARATOR.'browser';
    $relative = ltrim($path, '/');
    $candidate = $browser.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    $realBrowser = realpath($browser);
    $realFile = ($relative !== '' && is_file($candidate)) ? realpath($candidate) : false;

    if ($realBrowser && $realFile && str_starts_with($realFile, $realBrowser) && is_file($realFile)) {
        header('Content-Type: '.fileMime($realFile));
        readfile($realFile);
        exit;
    }

    $index = $browser.DIRECTORY_SEPARATOR.'index.html';
    if (! is_file($index)) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Portal ainda nao compilado. Na pasta do projeto, rode: npm run build:xampp';
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    readfile($index);
    exit;
}

function fileMime(string $path): string
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return match ($ext) {
        'js', 'mjs' => 'text/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'html' => 'text/html; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'txt', 'xml' => 'text/plain; charset=utf-8',
        default => function_exists('mime_content_type') ? (mime_content_type($path) ?: 'application/octet-stream') : 'application/octet-stream',
    };
}
