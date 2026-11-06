<?php

return [
    'disk' => env('BACKUP_DISK', env('PRIVATE_FILESYSTEM_DISK', 'local')),
    'path' => env('BACKUP_PATH', 'backups/database'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'timeout_seconds' => (int) env('BACKUP_TIMEOUT_SECONDS', 300),
    'schedule' => env('BACKUP_SCHEDULE', '02:30'),
    'max_restore_bytes' => (int) env('BACKUP_MAX_RESTORE_BYTES', 536870912),
    'encryption' => filter_var(env('BACKUP_ENCRYPTION', true), FILTER_VALIDATE_BOOL),
    'binaries' => [
        'mysqldump' => env('BACKUP_MYSQLDUMP_BINARY', 'mysqldump'),
        'mysql' => env('BACKUP_MYSQL_BINARY', 'mysql'),
        'pg_dump' => env('BACKUP_PG_DUMP_BINARY', 'pg_dump'),
        'psql' => env('BACKUP_PSQL_BINARY', 'psql'),
    ],
];
