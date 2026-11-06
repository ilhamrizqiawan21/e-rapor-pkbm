<?php

use App\Models\Institution;
use App\Services\AuditLogger;
use App\Services\Operations\DatabaseBackupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('backup:database', function (DatabaseBackupService $backup, AuditLogger $audit): int {
    try {
        $result = $backup->create();

        foreach (Institution::query()->get() as $institution) {
            $audit->recordForInstitution($institution, 'backup.created', 'backup', 0, null, [
                'name' => $result['name'],
                'driver' => $result['driver'],
                'size' => $result['size'],
                'checksum' => $result['checksum'],
                'automated' => true,
            ]);
        }

        $this->info('Backup berhasil: '.$result['name']);

        return 0;
    } catch (Throwable $exception) {
        Log::error('Database backup gagal.', ['exception' => $exception]);
        $this->error($exception->getMessage());

        return 1;
    }
})->purpose('Create a private database backup');

Schedule::command('backup:database')
    ->dailyAt((string) config('backup.schedule', '02:30'))
    ->withoutOverlapping()
    ->onOneServer();
