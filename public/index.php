<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$basePath = rtrim((string) parse_url((string) env('APP_URL', ''), PHP_URL_PATH), '/');

if ($basePath !== '') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
    $query = parse_url($requestUri, PHP_URL_QUERY);

    foreach ([$basePath.'/public', $basePath] as $prefix) {
        if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
            $stripped = substr($path, strlen($prefix)) ?: '/';
            $_SERVER['REQUEST_URI'] = $stripped.($query !== null && $query !== '' ? '?'.$query : '');
            break;
        }
    }
}

$app->handleRequest(Request::capture());
