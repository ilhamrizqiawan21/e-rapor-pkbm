<?php

/**
 * Normalize a request when Apache exposes the application below the document root.
 *
 * The value is supplied by the web server through ERAPOR_SUBPATH. Leave it empty
 * when the application is served from a domain or virtual host root.
 */
function normalizeApplicationSubpathRequest(): void
{
    $configuredSubpath = $_SERVER['ERAPOR_SUBPATH'] ?? $_SERVER['REDIRECT_ERAPOR_SUBPATH'] ?? '';
    $subpath = '/'.trim((string) $configuredSubpath, '/');

    if ($subpath === '/') {
        return;
    }

    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    $queryString = (string) ($_SERVER['QUERY_STRING'] ?? (parse_url($requestUri, PHP_URL_QUERY) ?: ''));

    if ($requestPath !== $subpath && ! str_starts_with($requestPath, $subpath.'/')) {
        return;
    }

    $_SERVER['ERAPOR_SUBPATH_REQUEST'] = '1';
    $normalizedPath = substr($requestPath, strlen($subpath)) ?: '/';
    $_SERVER['REQUEST_URI'] = $normalizedPath.($queryString !== '' ? '?'.$queryString : '');
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
}
