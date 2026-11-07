<?php

return [

    'seed_demo_data' => filter_var(
        env('ERAPOR_SEED_DEMO_DATA', in_array((string) env('APP_ENV', 'production'), ['local', 'testing'], true)),
        FILTER_VALIDATE_BOOL,
    ),

    'formatting' => [
        'date' => env('APP_DATE_FORMAT', 'd/m/Y'),
        'datetime' => env('APP_DATETIME_FORMAT', 'd/m/Y H:i'),
        'decimal_places' => (int) env('APP_DECIMAL_PLACES', 2),
        'decimal_separator' => env('APP_DECIMAL_SEPARATOR', ','),
        'thousands_separator' => env('APP_THOUSANDS_SEPARATOR', '.'),
    ],

    'storage' => [
        'private_disk' => env('PRIVATE_FILESYSTEM_DISK', 'local'),
        'public_disk' => env('PUBLIC_FILESYSTEM_DISK', 'public'),
        'report_archive_disk' => env('REPORT_ARCHIVE_DISK', 'local'),
    ],

    'browser_support' => [
        'minimum_major_versions' => 2,
        'requires_javascript' => true,
    ],

];
