<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecurityPrivacyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_security_headers_are_present_on_guest_responses(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_student_access_is_audited_without_recording_personal_data(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Privacy']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);
        $student = Student::query()->create([
            'institution_id' => $institution->id,
            'student_number' => 'PD-PRIVACY',
            'full_name' => 'Peserta Didik Privat',
            'address' => 'Alamat privat',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.students.show', $student))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'student.viewed',
            'entity_type' => Student::class,
            'entity_id' => $student->id,
        ]);

        $audit = AuditLog::query()->where('action', 'student.viewed')->latest('id')->firstOrFail();
        $this->assertNull($audit->old_value_json);
        $this->assertNull($audit->new_value_json);
        $this->assertStringNotContainsString('Alamat privat', $audit->toJson());
    }

    public function test_student_import_rejects_non_tabular_uploads(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Upload Security']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id]);

        $this->actingAs($admin)
            ->post(route('admin.students.import.preview'), [
                'file' => UploadedFile::fake()->create('payload.php', 20, 'text/x-php'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_sensitive_application_configuration_is_hardened(): void
    {
        $this->assertTrue((bool) config('session.http_only'));
        $this->assertSame('json', config('session.serialization'));
        $this->assertSame(120, config('session.lifetime'));
        $this->assertIsBool(config('security.hsts'));
    }
}
