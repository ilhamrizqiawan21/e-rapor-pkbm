<?php

namespace App\Services\Operations;

use App\Exceptions\BackupException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

final class DatabaseBackupService
{
    /** @return array{name: string, path: string, driver: string, size: int, checksum: string, created_at: Carbon, pruned: int} */
    public function create(): array
    {
        $driver = (string) config('database.connections.'.config('database.default').'.driver');
        $now = now();
        $extension = in_array($driver, ['sqlite'], true) ? 'sqlite' : 'sql.gz';
        $name = 'database-'.$driver.'-'.$now->format('Ymd-His-u').'.'.$extension;
        $relativePath = trim((string) config('backup.path', 'backups/database'), '/').'/'.$name;
        $disk = $this->disk();

        $this->ensureDirectory($disk, dirname($relativePath));

        try {
            if ($driver === 'sqlite') {
                $this->backupSqlite($disk, $relativePath);
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->backupSql($disk, $relativePath, $this->dumpMysql());
            } elseif ($driver === 'pgsql') {
                $this->backupSql($disk, $relativePath, $this->dumpPostgres());
            } else {
                throw new BackupException('Driver database '.$driver.' belum didukung untuk backup otomatis.');
            }
        } catch (BackupException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new BackupException('Backup database gagal: '.$exception->getMessage(), previous: $exception);
        }

        $pruned = $this->prune($now);

        return [
            'name' => $name,
            'path' => $relativePath,
            'driver' => $driver,
            'size' => $disk->size($relativePath),
            'checksum' => $this->checksum($disk, $relativePath),
            'created_at' => $now,
            'pruned' => $pruned,
        ];
    }

    /** @return array<int, array{name: string, path: string, size: int, modified_at: Carbon, checksum: string}> */
    public function list(): array
    {
        $disk = $this->disk();
        $prefix = trim((string) config('backup.path', 'backups/database'), '/');

        return collect($disk->files($prefix))
            ->filter(fn (string $path): bool => $this->isBackupName(basename($path)))
            ->map(function (string $path) use ($disk): array {
                return [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => $disk->size($path),
                    'modified_at' => Carbon::createFromTimestamp($disk->lastModified($path)),
                    'checksum' => $this->checksum($disk, $path),
                ];
            })
            ->sortByDesc('modified_at')
            ->values()
            ->all();
    }

    /** @return array{name: string, driver: string, safety_backup: array<string, mixed>, restored: bool} */
    public function restoreFromPath(string $sourcePath, string $originalName): array
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new BackupException('File backup tidak dapat dibaca.');
        }

        $safetyBackup = $this->create();
        $driver = (string) config('database.connections.'.config('database.default').'.driver');

        try {
            if ($driver === 'sqlite') {
                $this->restoreSqlite($sourcePath);
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->restoreSql($sourcePath, $originalName, $this->mysqlRestoreCommand());
            } elseif ($driver === 'pgsql') {
                $this->restoreSql($sourcePath, $originalName, $this->postgresRestoreCommand());
            } else {
                throw new BackupException('Driver database '.$driver.' belum didukung untuk restore.');
            }

            $this->smokeTest();
        } catch (BackupException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new BackupException('Restore database gagal. Safety backup tersimpan sebagai '.$safetyBackup['name'].'.', previous: $exception);
        }

        return [
            'name' => $originalName,
            'driver' => $driver,
            'safety_backup' => $safetyBackup,
            'restored' => true,
        ];
    }

    /** @return array{name: string, path: string} */
    public function downloadPath(string $name): array
    {
        if (basename($name) !== $name || ! $this->isBackupName($name)) {
            throw new BackupException('Nama file backup tidak valid.');
        }

        $path = trim((string) config('backup.path', 'backups/database'), '/').'/'.$name;
        if (! $this->disk()->exists($path)) {
            throw new BackupException('File backup tidak ditemukan.');
        }

        return ['name' => $name, 'path' => $path];
    }

    private function backupSqlite(Filesystem $disk, string $relativePath): void
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');
        if ($database === ':memory:') {
            throw new BackupException('Backup SQLite tidak dapat dibuat dari database in-memory.');
        }

        $source = $this->resolveDatabasePath($database);
        if (! is_file($source)) {
            throw new BackupException('File database SQLite tidak ditemukan.');
        }

        if (! $disk->put($relativePath, fopen($source, 'rb'))) {
            throw new BackupException('File backup SQLite gagal disimpan.');
        }
    }

    private function backupSql(Filesystem $disk, string $relativePath, string $sql): void
    {
        $compressed = gzencode($sql, 6);
        if ($compressed === false || ! $disk->put($relativePath, $compressed)) {
            throw new BackupException('Dump SQL gagal disimpan.');
        }
    }

    private function dumpMysql(): string
    {
        $connection = config('database.connections.'.config('database.default'));
        $process = new Process([
            (string) config('backup.binaries.mysqldump', 'mysqldump'),
            '--single-transaction',
            '--routines',
            '--events',
            '--skip-lock-tables',
            '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? '3306'),
            '--user='.(string) ($connection['username'] ?? 'root'),
            (string) ($connection['database'] ?? ''),
        ], null, ['MYSQL_PWD' => (string) ($connection['password'] ?? '')]);
        $process->setTimeout((int) config('backup.timeout_seconds', 300));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new BackupException('mysqldump gagal: '.trim($process->getErrorOutput()));
        }

        return $process->getOutput();
    }

    private function dumpPostgres(): string
    {
        $connection = config('database.connections.'.config('database.default'));
        $process = new Process([
            (string) config('backup.binaries.pg_dump', 'pg_dump'),
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? '5432'),
            '--username='.(string) ($connection['username'] ?? 'root'),
            (string) ($connection['database'] ?? ''),
        ], null, ['PGPASSWORD' => (string) ($connection['password'] ?? '')]);
        $process->setTimeout((int) config('backup.timeout_seconds', 300));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new BackupException('pg_dump gagal: '.trim($process->getErrorOutput()));
        }

        return $process->getOutput();
    }

    private function restoreSqlite(string $sourcePath): void
    {
        $header = file_get_contents($sourcePath, false, null, 0, 16);
        if ($header !== "SQLite format 3\000") {
            throw new BackupException('File restore bukan database SQLite yang valid.');
        }

        $database = (string) config('database.connections.'.config('database.default').'.database');
        if ($database === ':memory:') {
            throw new BackupException('Restore SQLite tidak dapat dilakukan ke database in-memory.');
        }

        $target = $this->resolveDatabasePath($database);
        $temporary = $target.'.restore-'.Str::uuid();
        if (! @copy($sourcePath, $temporary) || ! @rename($temporary, $target)) {
            @unlink($temporary);
            throw new BackupException('File database SQLite tidak dapat diganti.');
        }

        DB::purge(config('database.default'));
        DB::reconnect(config('database.default'));
    }

    /** @param array<int, string> $command */
    private function restoreSql(string $sourcePath, string $originalName, array $command): void
    {
        $contents = file_get_contents($sourcePath);
        if ($contents === false) {
            throw new BackupException('Isi dump SQL tidak dapat dibaca.');
        }
        if (str_ends_with(strtolower($originalName), '.gz')) {
            $contents = gzdecode($contents);
        }
        if (! is_string($contents) || trim($contents) === '') {
            throw new BackupException('Dump SQL kosong atau rusak.');
        }

        $process = new Process($command, null, $this->restoreEnvironment());
        $process->setInput($contents);
        $process->setTimeout((int) config('backup.timeout_seconds', 300));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new BackupException('Restore SQL gagal: '.trim($process->getErrorOutput()));
        }
    }

    /** @return array<int, string> */
    private function mysqlRestoreCommand(): array
    {
        $connection = config('database.connections.'.config('database.default'));

        return [
            (string) config('backup.binaries.mysql', 'mysql'),
            '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? '3306'),
            '--user='.(string) ($connection['username'] ?? 'root'),
            (string) ($connection['database'] ?? ''),
        ];
    }

    /** @return array<int, string> */
    private function postgresRestoreCommand(): array
    {
        $connection = config('database.connections.'.config('database.default'));

        return [
            (string) config('backup.binaries.psql', 'psql'),
            '--set=ON_ERROR_STOP=1',
            '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? '5432'),
            '--username='.(string) ($connection['username'] ?? 'root'),
            '--dbname='.(string) ($connection['database'] ?? ''),
        ];
    }

    /** @return array<string, string> */
    private function restoreEnvironment(): array
    {
        $connection = config('database.connections.'.config('database.default'));
        $driver = (string) $connection['driver'];

        return $driver === 'pgsql'
            ? ['PGPASSWORD' => (string) ($connection['password'] ?? '')]
            : ['MYSQL_PWD' => (string) ($connection['password'] ?? '')];
    }

    private function smokeTest(): void
    {
        DB::purge(config('database.default'));
        DB::reconnect(config('database.default'));
        DB::select('select 1');

        foreach (['migrations', 'users', 'institutions', 'audit_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new BackupException('Restore selesai tetapi tabel wajib '.$table.' tidak ditemukan.');
            }
        }
    }

    private function prune(Carbon $now): int
    {
        $days = max(1, (int) config('backup.retention_days', 30));
        $cutoff = $now->copy()->subDays($days);
        $deleted = 0;

        foreach ($this->list() as $backup) {
            if ($backup['modified_at']->lt($cutoff) && $this->disk()->delete($backup['path'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('backup.disk', 'local'));
    }

    private function ensureDirectory(Filesystem $disk, string $directory): void
    {
        if ($directory !== '' && ! $disk->directoryExists($directory)) {
            $disk->makeDirectory($directory);
        }
    }

    private function resolveDatabasePath(string $database): string
    {
        return Str::startsWith($database, ['/', '\\\\']) ? $database : base_path($database);
    }

    private function isBackupName(string $name): bool
    {
        return preg_match('/^database-(sqlite|mysql|mariadb|pgsql)-[0-9]{8}-[0-9]{6}-[0-9]{6}\\.(sqlite|sql\\.gz)$/', $name) === 1;
    }

    private function checksum(Filesystem $disk, string $path): string
    {
        if (method_exists($disk, 'path')) {
            $absolute = $disk->path($path);
            if (is_file($absolute)) {
                $checksum = hash_file('sha256', $absolute);
                if (is_string($checksum)) {
                    return $checksum;
                }
            }
        }

        return hash('sha256', $disk->get($path));
    }
}
