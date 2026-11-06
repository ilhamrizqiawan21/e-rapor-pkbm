<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Http\Controllers\Admin\InstitutionFormatSettingsController;
use App\Http\Requests\Admin\UpdateInstitutionFormatSettingsRequest;
use App\Models\Institution;
use App\Models\InstitutionFormatSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\InstitutionFormatSettingsService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;
use Tests\TestCase;

class InstitutionFormatSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_service_creates_safe_defaults_per_institution_and_renders_templates(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Format A']);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Format B']);
        $service = app(InstitutionFormatSettingsService::class);

        $settings = $service->forInstitution($institution);
        $otherSettings = $service->forInstitution($otherInstitution);

        $this->assertSame(InstitutionFormatSettingsService::DEFAULTS['date_format'], $settings->date_format);
        $this->assertTrue($settings->show_kkm);
        $this->assertTrue($settings->show_predicate);
        $this->assertNotSame($settings->getKey(), $otherSettings->getKey());

        $service->updateForInstitution($institution, [
            'student_name_format' => '{student_number} - {full_name}',
            'document_number_format' => 'RAPOR/{academic_year}/{document_number}',
            'date_format' => 'd F Y',
            'show_kkm' => false,
        ]);

        $this->assertSame('{full_name}', $otherSettings->refresh()->student_name_format);
        $this->assertSame('RAPOR/2025-2026/DOC-001', $service->formatDocumentNumber(
            'DOC-001',
            $settings->refresh(),
            ['academic_year' => '2025-2026'],
        ));
        $this->assertSame('06 November 2026', $service->formatDate('2026-11-06', $settings->refresh()));
    }

    public function test_student_name_format_uses_institution_settings(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Student Format']);
        $settings = app(InstitutionFormatSettingsService::class)->updateForInstitution($institution, [
            'student_name_format' => '{student_number} / {full_name}',
        ]);
        $student = Student::query()->create([
            'institution_id' => $institution->getKey(),
            'student_number' => 'PD-001',
            'full_name' => 'Siti Aminah',
        ]);

        $this->assertSame(
            'PD-001 / Siti Aminah',
            app(InstitutionFormatSettingsService::class)->formatStudentName($student, $settings),
        );
    }

    public function test_admin_controller_renders_format_settings_view_and_creates_defaults(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Controller']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $request = Request::create('/admin/settings/format', 'GET');
        $request->setUserResolver(fn (): User => $admin);

        $this->actingAs($admin);
        $controller = app(InstitutionFormatSettingsController::class);
        $response = app()->call([$controller, 'edit'], ['request' => $request]);

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('admin.settings.format', $response->name());
        $this->assertTrue($response->getData()['settings']->exists);
        $this->assertDatabaseHas('institution_format_settings', ['institution_id' => $institution->getKey()]);
    }

    public function test_admin_controller_updates_only_the_current_institution_settings(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Update']);
        $otherInstitution = Institution::query()->create(['name' => 'PKBM Other']);
        $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->getKey()]);
        $service = app(InstitutionFormatSettingsService::class);
        $service->forInstitution($otherInstitution);
        $request = UpdateInstitutionFormatSettingsRequest::create('/admin/settings/format', 'PUT', $this->validPayload([
            'student_name_format' => '{student_number} - {full_name}',
            'show_kkm' => 0,
        ]));
        $request->setUserResolver(fn (): User => $admin);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->validateResolved();

        $this->actingAs($admin);
        $controller = app(InstitutionFormatSettingsController::class);
        app()->call([$controller, 'update'], ['request' => $request]);

        $this->assertDatabaseHas('institution_format_settings', [
            'institution_id' => $institution->getKey(),
            'student_name_format' => '{student_number} - {full_name}',
            'show_kkm' => 0,
        ]);
        $this->assertDatabaseHas('institution_format_settings', [
            'institution_id' => $otherInstitution->getKey(),
            'student_name_format' => '{full_name}',
            'show_kkm' => 1,
        ]);
    }

    public function test_non_admin_cannot_authorize_format_settings_update(): void
    {
        $institution = Institution::query()->create(['name' => 'PKBM Non Admin']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->getKey()]);
        $request = UpdateInstitutionFormatSettingsRequest::create('/admin/settings/format', 'PUT', $this->validPayload());
        $request->setUserResolver(fn (): User => $guru);

        $this->assertFalse($request->authorize());
    }

    public function test_format_settings_view_exposes_all_pdf_and_visibility_controls(): void
    {
        $settings = new InstitutionFormatSetting(InstitutionFormatSettingsService::DEFAULTS);
        $html = view('admin.settings.format', [
            'settings' => $settings,
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('Format nama Peserta Didik', $html);
        $this->assertStringContainsString('Format nomor dokumen', $html);
        $this->assertStringContainsString('Margin atas (mm)', $html);
        $this->assertStringContainsString('Tampilkan KKM / nilai minimum', $html);
        $this->assertStringContainsString('Tampilkan predikat', $html);
        $this->assertStringContainsString('Tanda tangan', $html);
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'student_name_format' => '{full_name}',
            'document_number_format' => '{document_number}',
            'date_format' => 'd/m/Y',
            'signature_left_role' => 'GURU',
            'signature_left_label' => 'Wali Kelas',
            'signature_right_role' => 'KEPSEK',
            'signature_right_label' => 'Kepala PKBM',
            'show_logo' => 1,
            'logo_position' => 'center',
            'logo_width_mm' => 25,
            'margin_top_mm' => 15,
            'margin_right_mm' => 15,
            'margin_bottom_mm' => 15,
            'margin_left_mm' => 15,
            'header_text' => null,
            'footer_text' => null,
            'show_kkm' => 1,
            'show_predicate' => 1,
        ], $overrides);
    }
}
