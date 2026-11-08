<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Normalize requests when Apache exposes public/ through the application subpath.
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$queryString = (string) ($_SERVER['QUERY_STRING'] ?? (parse_url($requestUri, PHP_URL_QUERY) ?: ''));
$subpath = '/e-rapor-pkbm';

if ($requestPath === $subpath || str_starts_with($requestPath, $subpath.'/')) {
    $_SERVER['ERAPOR_SUBPATH_REQUEST'] = '1';
    $normalizedPath = substr($requestPath, strlen($subpath)) ?: '/';
    $_SERVER['REQUEST_URI'] = $normalizedPath.($queryString !== '' ? '?'.$queryString : '');
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
