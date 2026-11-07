<?php

namespace Tests\Feature;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeImportBatch;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Support\SimpleXlsx;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GradeImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_official_template_is_contextual_and_valid_workbook_can_be_previewed_and_imported(): void
    {
        [$guru, $assignment, $semester, $first, $second] = $this->academicSetup(twoStudents: true);
        $context = $this->context($assignment, $semester);

        $this->actingAs($guru)->get(route('admin.grade-entry.import.create', $context))->assertOk();

        $templateResponse = $this->actingAs($guru)->get(route('admin.grade-entry.import.template', $context));
        $templateResponse->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $templateResponse->streamedContent());

        $file = $this->workbook($context, [
            [$first->id, 'PD-001', '', 'Siswa Uji', '90'],
            [$second->id, 'PD-002', '', 'Siswa Kedua', ''],
        ], 'memahami pengertian sifat terpuji');

        $preview = $this->actingAs($guru)->post(route('admin.grade-entry.import.preview'), [
            ...$context,
            'file' => $file,
        ]);
        $preview->assertOk()->assertSee('Semua baris lolos validasi')->assertSee('Siswa sangat baik dalam memahami pengertian sifat terpuji.');

        $batch = GradeImportBatch::query()->latest('id')->firstOrFail();
        $this->actingAs($guru)->post(route('admin.grade-entry.import.store'), ['token' => $batch->token])
            ->assertRedirect(route('admin.grade-entry.index', $context));

        $this->assertDatabaseHas('report_grades', [
            'score' => 90,
            'predicate' => 'A',
            'description' => 'Siswa sangat baik dalam memahami pengertian sifat terpuji.',
        ]);
        $this->assertDatabaseCount('report_grades', 1);
        $this->assertDatabaseHas('grade_import_batches', ['status' => 'IMPORTED', 'imported_rows' => 1]);
    }

    public function test_official_template_has_readable_sheet_layout_and_distinguishes_editable_cells(): void
    {
        [$guru, $assignment, $semester] = $this->academicSetup();
        $context = $this->context($assignment, $semester);

        $content = $this->actingAs($guru)
            ->get(route('admin.grade-entry.import.template', $context))
            ->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'grade-template-format-');
        $this->assertNotFalse($path);
        file_put_contents($path, $content);

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $instructions = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $contextSheet = (string) $zip->getFromName('xl/worksheets/sheet2.xml');
        $grades = (string) $zip->getFromName('xl/worksheets/sheet3.xml');
        $styles = (string) $zip->getFromName('xl/styles.xml');
        $zip->close();
        unlink($path);

        $this->assertStringContainsString('showGridLines="0"', $instructions);
        $this->assertStringContainsString('width="112"', $instructions);
        $this->assertStringContainsString('wrapText="1"', $styles);
        $this->assertStringContainsString('width="64"', $contextSheet);
        $this->assertStringContainsString('tabColor rgb="FF2563EB"', $grades);
        $this->assertStringContainsString('width="42"', $grades);
        $this->assertStringContainsString('s="2"', $grades);
        $this->assertStringContainsString('s="3"', $grades);
    }

    public function test_changed_identity_and_out_of_range_score_block_the_atomic_import(): void
    {
        [$guru, $assignment, $semester, $first, $second] = $this->academicSetup(twoStudents: true);
        $context = $this->context($assignment, $semester);
        $file = $this->workbook($context, [
            [$first->id, 'PD-001', '', 'Nama Diubah', '101'],
            [$second->id, 'PD-002', '', 'Siswa Kedua', '80'],
        ], 'memahami bilangan');

        $this->actingAs($guru)->post(route('admin.grade-entry.import.preview'), [...$context, 'file' => $file])
            ->assertOk()
            ->assertSee('Perbaiki file sebelum impor')
            ->assertSee('Nama peserta didik tidak sesuai dengan data server.')
            ->assertSee('Nilai harus berada pada rentang 0 sampai 100.');

        $batch = GradeImportBatch::query()->latest('id')->firstOrFail();
        $this->actingAs($guru)->post(route('admin.grade-entry.import.store'), ['token' => $batch->token])
            ->assertRedirect()->assertSessionHasErrors('file');
        $this->assertDatabaseCount('report_grades', 0);
    }

    public function test_kepsek_cannot_use_grade_import_and_other_guru_cannot_use_assignment_template(): void
    {
        [$guru, $assignment, $semester] = $this->academicSetup();
        $kepsek = User::factory()->withRole(RoleCode::Kepsek)->create(['institution_id' => $guru->institution_id]);
        $otherGuru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $guru->institution_id]);
        $context = $this->context($assignment, $semester);

        $this->actingAs($kepsek)->get(route('admin.grade-entry.import.create', $context))->assertForbidden();
        $this->actingAs($otherGuru)->get(route('admin.grade-entry.import.template', $context))->assertForbidden();
    }

    /** @return array<string, int> */
    private function context(SubjectAssignment $assignment, Semester $semester): array
    {
        return [
            'academic_year_id' => $assignment->academicClass->academic_year_id,
            'semester_id' => $semester->id,
            'class_id' => $assignment->class_id,
            'subject_id' => $assignment->subject_id,
            'subject_assignment_id' => $assignment->id,
        ];
    }

    private function workbook(array $context, array $rows, string $material): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'grade-test-');
        $this->assertNotFalse($path);
        $xlsxPath = $path.'.xlsx';
        rename($path, $xlsxPath);
        $path = $xlsxPath;
        SimpleXlsx::write($path, [
            'PETUNJUK' => [['BAGIAN', 'PETUNJUK IMPORT NILAI']],
            'KONTEKS' => [
                ['FIELD', 'VALUE', 'KETERANGAN'],
                ['TEMPLATE_VERSION', 'GRADE-INPUT-V1', ''],
                ['ACADEMIC_YEAR_ID', (string) $context['academic_year_id'], ''],
                ['SEMESTER_ID', (string) $context['semester_id'], ''],
                ['CLASS_ID', (string) $context['class_id'], ''],
                ['SUBJECT_ASSIGNMENT_ID', (string) $context['subject_assignment_id'], ''],
                ['MATERI', $material, ''],
                ['BANK_DESKRIPSI_ID', '', ''],
            ],
            'NILAI' => [
                ['NO', 'ENROLLMENT_ID', 'NIPD', 'NISN', 'NAMA LENGKAP', 'NILAI (0-100)', 'PREDIKAT (SERVER)', 'DESKRIPSI OTOMATIS (SERVER)', 'STATUS (SERVER)'],
                ...array_map(static fn (array $row, int $index): array => [$index + 1, ...$row, '—', 'diabaikan', 'diabaikan'], $rows, array_keys($rows)),
            ],
        ], ['NILAI' => [5]], ['KONTEKS' => ['B7']]);

        $content = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('template-nilai.xlsx', $content);
    }

    /** @return array<int, mixed> */
    private function academicSetup(bool $twoStudents = false): array
    {
        $institution = Institution::query()->create(['name' => 'PKBM Grade Import Test']);
        $guru = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id]);
        $year = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE']);
        $semester = Semester::query()->create(['academic_year_id' => $year->id, 'code' => 'GANJIL', 'name' => 'Ganjil', 'sequence' => 1, 'status' => 'ACTIVE']);
        $program = Program::query()->create(['institution_id' => $institution->id, 'code' => 'A', 'name' => 'Paket A']);
        $level = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'program_id' => $program->id, 'level_id' => $level->id, 'code' => 'A-1', 'name' => 'Kelas 1 A', 'status' => 'ACTIVE']);
        $subject = Subject::query()->create(['institution_id' => $institution->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'active' => true]);
        $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $guru->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
        $firstStudent = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-001', 'full_name' => 'Siswa Uji', 'status' => 'ACTIVE']);
        $first = Enrollment::query()->create(['student_id' => $firstStudent->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);

        if (! $twoStudents) {
            return [$guru, $assignment->load(['academicClass', 'subject', 'teacher']), $semester, $first];
        }

        $secondStudent = Student::query()->create(['institution_id' => $institution->id, 'student_number' => 'PD-002', 'full_name' => 'Siswa Kedua', 'status' => 'ACTIVE']);
        $second = Enrollment::query()->create(['student_id' => $secondStudent->id, 'class_id' => $class->id, 'academic_year_id' => $year->id, 'start_date' => '2026-07-01', 'enrollment_status' => 'ACTIVE']);

        return [$guru, $assignment->load(['academicClass', 'subject', 'teacher']), $semester, $first, $second];
    }
}
