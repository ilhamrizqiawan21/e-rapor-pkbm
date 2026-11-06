<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\AuditHistoryController;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use LogicException;
use Tests\TestCase;

class AuditHistoryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])
            ->get('admin/audit-logs', [AuditHistoryController::class, 'index'])
            ->name('admin.audit-logs.index');
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_admin_can_filter_immutable_audit_history(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Audit']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id, 'name' => 'Admin Audit']);
        $other = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id, 'name' => 'Guru Audit']);

        AuditLog::query()->create([
            'institution_id' => $institution->id,
            'user_id' => $admin->id,
            'action' => 'report_card.locked',
            'entity_type' => 'App\\Models\\ReportCard',
            'entity_id' => 41,
            'old_value_json' => ['status' => 'APPROVED'],
            'new_value_json' => ['status' => 'LOCKED'],
            'reason' => 'Arsip semester.',
            'created_at' => now()->subDay(),
        ]);
        AuditLog::query()->create([
            'institution_id' => $institution->id,
            'user_id' => $other->id,
            'action' => 'student.class_transferred',
            'entity_type' => 'App\\Models\\Enrollment',
            'entity_id' => 42,
            'reason' => 'Perpindahan kelas.',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', [
                'actor_id' => $admin->id,
                'action' => 'report_card.locked',
                'entity_type' => 'report_card',
                'entity_id' => 41,
            ]))
            ->assertOk()
            ->assertSee('Histori audit')
            ->assertSee('Arsip semester.')
            ->assertDontSee('Perpindahan kelas.');
    }

    public function test_guru_cannot_open_audit_history(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Audit Permission']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);

        $this->actingAs($guru)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_audit_logs_cannot_be_updated_or_deleted(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Audit Immutable']);
        $log = AuditLog::query()->create([
            'institution_id' => $institution->id,
            'action' => 'CREATE',
            'entity_type' => 'App\\Models\\Institution',
            'entity_id' => $institution->id,
        ]);

        $this->expectException(LogicException::class);
        $log->update(['reason' => 'Tidak boleh']);
    }
}
