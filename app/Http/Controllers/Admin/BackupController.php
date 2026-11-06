<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestoreBackupRequest;
use App\Services\AuditLogger;
use App\Services\Operations\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class BackupController extends Controller
{
    public function index(Request $request, DatabaseBackupService $backup): View
    {
        abort_unless($request->user()->hasRole('ADMIN'), 403);

        return view('admin.backups.index', [
            'backups' => $backup->list(),
            'driver' => config('database.default'),
            'schedule' => config('backup.schedule'),
            'retentionDays' => config('backup.retention_days'),
        ]);
    }

    public function store(Request $request, DatabaseBackupService $backup, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('ADMIN'), 403);

        try {
            $result = $backup->create();
            $audit->record($request->user(), 'backup.created', 'backup', 0, null, [
                'name' => $result['name'],
                'driver' => $result['driver'],
                'size' => $result['size'],
                'checksum' => $result['checksum'],
            ]);

            return back()->with('status', 'Backup berhasil dibuat: '.$result['name']);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => $this->errorMessage($exception, 'Backup database gagal. Periksa log aplikasi atau hubungi Admin sistem.')]);
        }
    }

    public function restore(RestoreBackupRequest $request, DatabaseBackupService $backup, AuditLogger $audit): RedirectResponse
    {
        $file = $request->file('backup');
        abort_unless($file !== null, 422);

        try {
            $result = $backup->restoreFromPath($file->getPathname(), $file->getClientOriginalName());
            $audit->record($request->user(), 'backup.restored', 'backup', 0, null, [
                'name' => $result['name'],
                'driver' => $result['driver'],
                'safety_backup' => $result['safety_backup']['name'],
            ], 'Restore dikonfirmasi dengan token RESTORE.');

            return back()->with('status', 'Database berhasil dipulihkan. Safety backup tersimpan sebagai '.$result['safety_backup']['name'].'.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => $this->errorMessage($exception, 'Restore database gagal. Safety backup mungkin sudah dibuat; periksa log aplikasi.')]);
        }
    }

    public function download(string $backup, Request $request, DatabaseBackupService $service, AuditLogger $audit): StreamedResponse
    {
        abort_unless($request->user()->hasRole('ADMIN'), 403);

        $file = $service->downloadPath($backup);
        $audit->record($request->user(), 'backup.downloaded', 'backup', 0, null, ['name' => $file['name']]);

        return Storage::disk((string) config('backup.disk', 'local'))->download($file['path'], $file['name']);
    }

    private function errorMessage(Throwable $exception, string $fallback): string
    {
        return config('app.debug') ? $exception->getMessage() : $fallback;
    }
}
