<?php

return [
    // Force-HTTPS is enabled by default outside local development. The web server
    // or trusted proxy remains responsible for redirecting the initial HTTP request.
    'force_https' => filter_var(
        env('SECURITY_FORCE_HTTPS', in_array((string) env('APP_ENV', 'production'), ['staging', 'production'], true)),
        FILTER_VALIDATE_BOOL,
    ),
    // HSTS is intentionally separate from force_https so operators can roll out
    // redirects before browsers cache the one-year transport policy.
    'hsts' => filter_var(
        env('SECURITY_HSTS', in_array((string) env('APP_ENV', 'production'), ['staging', 'production'], true)),
        FILTER_VALIDATE_BOOL,
    ),
];
