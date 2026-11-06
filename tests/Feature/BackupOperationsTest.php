<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\Institution;
use App\Models\User;
use App\Services\Operations\DatabaseBackupService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;

class BackupOperationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @var array<int, string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_sqlite_backup_is_timestamped_private_and_checksumed(): void
    {
        $database = $this->sqliteFile(['marker' => 'current']);
        $this->configureSqlite($database);
        Storage::fake('backup-test');
        config(['backup.disk' => 'backup-test', 'backup.encryption' => false]);

        $result = app(DatabaseBackupService::class)->create();

        $this->assertMatchesRegularExpression('/^database-sqlite-\d{8}-\d{6}-\d{6}\.sqlite$/', $result['name']);
        Storage::disk('backup-test')->assertExists($result['path']);
        $this->assertSame(hash('sha256', Storage::disk('backup-test')->get($result['path'])), $result['checksum']);
        $this->assertStringStartsWith("SQLite format 3\000", Storage::disk('backup-test')->get($result['path']));
    }

    public function test_sqlite_restore_creates_safety_backup_and_runs_smoke_test(): void
    {
        $target = $this->sqliteFile(['marker' => 'current']);
        $source = $this->sqliteFile(['marker' => 'restored']);
        $this->configureSqlite($target);
        Storage::fake('backup-test');
        config(['backup.disk' => 'backup-test', 'backup.encryption' => false]);

        $result = app(DatabaseBackupService::class)->restoreFromPath($source, 'client-backup.sqlite');

        $this->assertTrue($result['restored']);
        $this->assertNotSame($result['name'], $result['safety_backup']['name']);
        $this->assertSame('restored', $this->sqliteMarker($target));
        Storage::disk('backup-test')->assertExists($result['safety_backup']['path']);
    }

    public function test_sqlite_backup_can_be_encrypted_and_restored(): void
    {
        $target = $this->sqliteFile(['marker' => 'current']);
        $source = $this->sqliteFile(['marker' => 'restored-encrypted']);
        Storage::fake('backup-test');
        config(['backup.disk' => 'backup-test', 'backup.encryption' => true]);

        $this->configureSqlite($source);
        $backup = app(DatabaseBackupService::class)->create();

        $this->assertStringEndsWith('.sqlite.enc', $backup['name']);
        $this->assertFalse(str_starts_with(Storage::disk('backup-test')->get($backup['path']), "SQLite format 3\000"));

        $encrypted = tempnam(sys_get_temp_dir(), 'erapor-encrypted-');
        $this->assertNotFalse($encrypted);
        $this->temporaryFiles[] = $encrypted;
        file_put_contents($encrypted, Storage::disk('backup-test')->get($backup['path']));

        $this->configureSqlite($target);
        app(DatabaseBackupService::class)->restoreFromPath($encrypted, $backup['name']);

        $this->assertSame('restored-encrypted', $this->sqliteMarker($target));
    }

    public function test_only_admin_can_open_backup_operations(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Backup']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);

        $this->actingAs($admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Backup dan restore')
            ->assertSee('Belum ada backup database.');

        $this->actingAs($guru)
            ->get(route('admin.backups.index'))
            ->assertForbidden();
    }

    /** @param array<string, string> $tables */
    private function sqliteFile(array $tables): string
    {
        $path = tempnam(sys_get_temp_dir(), 'erapor-backup-');
        $this->assertNotFalse($path);
        $this->temporaryFiles[] = $path;
        $pdo = new PDO('sqlite:'.$path);

        foreach ($tables as $table => $value) {
            $pdo->exec('CREATE TABLE '.(in_array($table, ['marker'], true) ? $table : 'marker').' (value TEXT)');
            $pdo->prepare('INSERT INTO marker (value) VALUES (?)')->execute([$value]);
        }

        foreach (['migrations', 'users', 'institutions', 'audit_logs'] as $table) {
            if ($table !== 'marker') {
                $pdo->exec('CREATE TABLE '.$table.' (id INTEGER PRIMARY KEY)');
            }
        }

        return $path;
    }

    private function configureSqlite(string $database): void
    {
        config([
            'database.default' => 'backup_sqlite',
            'database.connections.backup_sqlite' => [
                'driver' => 'sqlite',
                'database' => $database,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
    }

    private function sqliteMarker(string $database): string
    {
        return (string) (new PDO('sqlite:'.$database))->query('SELECT value FROM marker LIMIT 1')->fetchColumn();
    }
}
