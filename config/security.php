<?php

return [
    'force_https' => filter_var(
        env('SECURITY_FORCE_HTTPS', in_array((string) env('APP_ENV', 'production'), ['staging', 'production'], true)),
        FILTER_VALIDATE_BOOL,
    ),
    'hsts' => filter_var(
        env('SECURITY_HSTS', in_array((string) env('APP_ENV', 'production'), ['staging', 'production'], true)),
        FILTER_VALIDATE_BOOL,
    ),
];
