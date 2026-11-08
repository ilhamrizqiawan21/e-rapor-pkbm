<?php

// The application is exposed below the Apache document root at /e-rapor-pkbm.
// Normalize that prefix before Laravel's request object resolves routes.
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

require __DIR__.'/public/index.php';
