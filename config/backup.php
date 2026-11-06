<?php

return [
    // Backups use a private disk by default; public media disks must be opted into
    // explicitly because database dumps contain student and staff personal data.
    'disk' => env('BACKUP_DISK', env('PRIVATE_FILESYSTEM_DISK', 'local')),
    'path' => env('BACKUP_PATH', 'backups/database'),
    // Retention is applied after each successful backup so manual and scheduled
    // runs follow the same storage bound.
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'timeout_seconds' => (int) env('BACKUP_TIMEOUT_SECONDS', 300),
    'schedule' => env('BACKUP_SCHEDULE', '02:30'),
    // Restore is deliberately bounded before any temporary file or database
    // process is started, limiting resource exhaustion from uploaded dumps.
    'max_restore_bytes' => (int) env('BACKUP_MAX_RESTORE_BYTES', 536870912),
    // Encryption uses Laravel's APP_KEY; changing that key intentionally makes
    // old encrypted backups unreadable and must be handled as a key-rotation event.
    'encryption' => filter_var(env('BACKUP_ENCRYPTION', true), FILTER_VALIDATE_BOOL),
    'binaries' => [
        'mysqldump' => env('BACKUP_MYSQLDUMP_BINARY', 'mysqldump'),
        'mysql' => env('BACKUP_MYSQL_BINARY', 'mysql'),
        'pg_dump' => env('BACKUP_PG_DUMP_BINARY', 'pg_dump'),
        'psql' => env('BACKUP_PSQL_BINARY', 'psql'),
    ],
];
