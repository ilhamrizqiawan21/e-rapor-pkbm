<?php

namespace Database\Seeders;

use App\Enums\DescriptionSnippetVisibility;
use App\Enums\ReportSignerType;
use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicDecision;
use App\Models\AcademicDecisionBatch;
use App\Models\AcademicYear;
use App\Models\AttendanceSummary;
use App\Models\AttitudeAssessment;
use App\Models\AuditLog;
use App\Models\ClassTransfer;
use App\Models\DescriptionSnippet;
use App\Models\DescriptionSnippetTemplate;
use App\Models\Enrollment;
use App\Models\GradeEntryContext;
use App\Models\GradeImportBatch;
use App\Models\HomeroomAssignment;
use App\Models\HomeroomNote;
use App\Models\Institution;
use App\Models\PeriodPlacement;
use App\Models\PeriodPreparation;
use App\Models\PredicateRange;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\ReportExtracurricular;
use App\Models\ReportGrade;
use App\Models\ReportSignature;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\InstitutionFormatSettingsService;
use App\Services\ReportCardIdentityService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $institution = Institution::query()->where('name', 'PKBM Contoh')->firstOrFail();
            $users = $this->seedUsers($institution);
            $this->seedInstitutionSettings($institution);
            $periods = $this->seedAcademicPeriods($institution);
            $subjects = $this->seedSubjects($institution);
            $snippets = $this->seedDescriptionSnippets($institution, $subjects, $users);

            $closedClasses = $this->seedClosedYearClasses($institution, $periods['closedYear'], $users['guru'], $users['admin']);
            $closedAssignments = $this->seedSubjectAssignments($closedClasses, $subjects, $users);

            $activeClasses = $this->seedClasses($institution, $periods['activeYear'], $users);
            $activeAssignments = $this->seedSubjectAssignments($activeClasses, $subjects, $users);

            $this->seedGradeEntryContexts($institution, $periods['activeSemester'], $activeAssignments, $snippets);

            $students = $this->seedStudents($institution);
            $closedEnrollments = $this->seedClosedYearEnrollmentsAndDecisions(
                $institution,
                $periods,
                $closedClasses,
                $activeClasses,
                $students,
                $closedAssignments,
                $subjects,
                $users,
            );

            $activeEnrollments = $this->seedActiveEnrollments(
                $periods['activeYear'],
                $activeClasses,
                $students,
                $users['admin'],
                $closedEnrollments,
            );

            $this->seedReportCards(
                $institution,
                $periods['activeSemester'],
                $activeEnrollments,
                $activeAssignments,
                $subjects,
                $users,
                ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NEEDS_REVISION', 'APPROVED', 'LOCKED'],
                Carbon::create(2026, 10, 27),
            );

            $this->seedGradeImportBatches(
                $institution,
                $periods['activeYear'],
                $periods['activeSemester'],
                $activeClasses,
                $activeAssignments,
                $activeEnrollments,
                $users['guru'],
            );
        });
    }

    /** @return array<string, User> */
    private function seedUsers(Institution $institution): array
    {
        $roleModels = collect(RoleCode::cases())->mapWithKeys(function (RoleCode $role): array {
            return [$role->value => Role::query()->where('code', $role->value)->firstOrFail()];
        });

        $definitions = [
            ['username' => 'admin', 'name' => 'Admin Demo', 'email' => 'admin@erapor.test', 'employee_number' => 'ADM-001', 'title' => 'Administrator', 'role' => RoleCode::Admin],
            ['username' => 'guru', 'name' => 'Budi Santoso', 'email' => 'guru@erapor.test', 'employee_number' => 'GRU-001', 'title' => 'Guru Kelas', 'role' => RoleCode::Guru],
            ['username' => 'kepsek', 'name' => 'Kepala PKBM Demo', 'email' => 'kepsek@erapor.test', 'employee_number' => 'KPK-001', 'title' => 'Kepala PKBM', 'role' => RoleCode::Kepsek],
        ];

        $users = [];

        foreach ($definitions as $definition) {
            $user = User::query()->updateOrCreate(
                ['username' => $definition['username']],
                [
                    'institution_id' => $institution->getKey(),
                    'name' => $definition['name'],
                    'email' => $definition['email'],
                    'password' => 'password',
                    'status' => 'ACTIVE',
                ],
            );

            $user->roles()->syncWithoutDetaching([$roleModels[$definition['role']->value]->getKey()]);

            StaffProfile::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'employee_number' => $definition['employee_number'],
                    'title' => $definition['title'],
                    'phone' => '0812-0000-'.str_pad((string) $user->getKey(), 4, '0', STR_PAD_LEFT),
                ],
            );

            $users[$definition['username']] = $user;
        }

        return $users;
    }

    /** @return array{closedYear: AcademicYear, closedGenap: Semester, activeYear: AcademicYear, activeSemester: Semester} */
    private function seedAcademicPeriods(Institution $institution): array
    {
        $closedYear = AcademicYear::query()->updateOrCreate(
            ['institution_id' => $institution->getKey(), 'name' => '2025/2026'],
            [
                'start_date' => '2025-07-01',
                'end_date' => '2026-06-30',
                'status' => 'CLOSED',
            ],
        );

        $activeYear = AcademicYear::query()->updateOrCreate(
            ['institution_id' => $institution->getKey(), 'name' => '2026/2027'],
            [
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'status' => 'ACTIVE',
            ],
        );

        $this->seedSemester($closedYear, 'GANJIL', 'Semester Ganjil', 1, 'CLOSED');
        $closedGenap = $this->seedSemester($closedYear, 'GENAP', 'Semester Genap', 2, 'CLOSED');
        $activeSemester = $this->seedSemester($activeYear, 'GANJIL', 'Semester Ganjil', 1, 'ACTIVE');
        $this->seedSemester($activeYear, 'GENAP', 'Semester Genap', 2, 'DRAFT');

        return [
            'closedYear' => $closedYear,
            'closedGenap' => $closedGenap,
            'activeYear' => $activeYear,
            'activeSemester' => $activeSemester,
        ];
    }

    private function seedSemester(AcademicYear $academicYear, string $code, string $name, int $sequence, string $status): Semester
    {
        return Semester::query()->updateOrCreate(
            ['academic_year_id' => $academicYear->getKey(), 'code' => $code],
            compact('name', 'sequence', 'status'),
        );
    }

    /** @return array<string, AcademicClass> */
    private function seedClosedYearClasses(Institution $institution, AcademicYear $closedYear, User $teacher, User $admin): array
    {
        $definitions = [
            ['code' => 'PA-6-25', 'name' => 'Paket A Tingkat 6 (2025/2026)', 'program' => 'PA', 'level' => 6],
            ['code' => 'PB-7-25', 'name' => 'Paket B Tingkat 7A (2025/2026)', 'program' => 'PB', 'level' => 7],
            ['code' => 'PB-9-25', 'name' => 'Paket B Tingkat 9A (2025/2026)', 'program' => 'PB', 'level' => 9],
            ['code' => 'PC-12-25', 'name' => 'Paket C Tingkat 12A (2025/2026)', 'program' => 'PC', 'level' => 12],
        ];

        $classes = [];

        foreach ($definitions as $definition) {
            $program = Program::query()->where('institution_id', $institution->getKey())->where('code', $definition['program'])->firstOrFail();
            $level = $program->levels()->where('number', $definition['level'])->firstOrFail();

            $academicClass = AcademicClass::query()->updateOrCreate(
                ['academic_year_id' => $closedYear->getKey(), 'code' => $definition['code']],
                [
                    'program_id' => $program->getKey(),
                    'level_id' => $level->getKey(),
                    'homeroom_teacher_id' => $teacher->getKey(),
                    'name' => $definition['name'],
                    'status' => 'CLOSED',
                ],
            );

            HomeroomAssignment::query()->updateOrCreate(
                ['academic_class_id' => $academicClass->getKey(), 'teacher_id' => $teacher->getKey(), 'status' => 'ENDED'],
                [
                    'assigned_by_id' => $admin->getKey(),
                    'assigned_at' => Carbon::create(2025, 7, 1, 8),
                    'ended_at' => Carbon::create(2026, 6, 30, 16),
                ],
            );

            $classes[$definition['code']] = $academicClass;
        }

        return $classes;
    }

    /** @param array<string, User> $users @return array<string, AcademicClass> */
    private function seedClasses(Institution $institution, AcademicYear $academicYear, array $users): array
    {
        $definitions = [
            ['code' => 'PA-1', 'name' => 'Paket A Tingkat 1', 'program' => 'PA', 'level' => 1, 'teacher' => 'guru'],
            ['code' => 'PA-6', 'name' => 'Paket A Tingkat 6', 'program' => 'PA', 'level' => 6, 'teacher' => 'guru'],
            ['code' => 'PB-7', 'name' => 'Paket B Tingkat 7A', 'program' => 'PB', 'level' => 7, 'teacher' => 'guru'],
            ['code' => 'PB-8', 'name' => 'Paket B Tingkat 8A', 'program' => 'PB', 'level' => 8, 'teacher' => 'guru'],
            ['code' => 'PB-9', 'name' => 'Paket B Tingkat 9A', 'program' => 'PB', 'level' => 9, 'teacher' => 'guru'],
            ['code' => 'PC-10', 'name' => 'Paket C Tingkat 10A', 'program' => 'PC', 'level' => 10, 'teacher' => 'guru'],
            ['code' => 'PC-12', 'name' => 'Paket C Tingkat 12A', 'program' => 'PC', 'level' => 12, 'teacher' => 'guru'],
        ];

        $classes = [];

        foreach ($definitions as $definition) {
            $program = Program::query()->where('institution_id', $institution->getKey())->where('code', $definition['program'])->firstOrFail();
            $level = $program->levels()->where('number', $definition['level'])->firstOrFail();
            $teacher = $users[$definition['teacher']];

            $academicClass = AcademicClass::query()->updateOrCreate(
                ['academic_year_id' => $academicYear->getKey(), 'code' => $definition['code']],
                [
                    'program_id' => $program->getKey(),
                    'level_id' => $level->getKey(),
                    'homeroom_teacher_id' => $teacher->getKey(),
                    'name' => $definition['name'],
                    'status' => 'ACTIVE',
                ],
            );

            HomeroomAssignment::query()->updateOrCreate(
                ['academic_class_id' => $academicClass->getKey(), 'teacher_id' => $teacher->getKey(), 'status' => 'ACTIVE'],
                [
                    'assigned_by_id' => $users['admin']->getKey(),
                    'assigned_at' => Carbon::create(2026, 7, 1, 8),
                    'ended_at' => null,
                ],
            );

            $classes[$definition['code']] = $academicClass;
        }

        return $classes;
    }

    /** @return array<string, Subject> */
    private function seedSubjects(Institution $institution): array
    {
        $definitions = [
            ['code' => 'PAI', 'name' => 'Pendidikan Agama dan Budi Pekerti', 'short_name' => 'Agama', 'category' => 'Keagamaan'],
            ['code' => 'PPKN', 'name' => 'Pendidikan Pancasila', 'short_name' => 'PPKn', 'category' => 'Umum'],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'short_name' => 'B. Indonesia', 'category' => 'Umum'],
            ['code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'Matematika', 'category' => 'Umum'],
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'short_name' => 'IPA', 'category' => 'Umum'],
            ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'short_name' => 'IPS', 'category' => 'Umum'],
            ['code' => 'BIG', 'name' => 'Bahasa Inggris', 'short_name' => 'B. Inggris', 'category' => 'Umum'],
            ['code' => 'PJOK', 'name' => 'Pendidikan Jasmani dan Kesehatan', 'short_name' => 'PJOK', 'category' => 'Keterampilan'],
            ['code' => 'SBK', 'name' => 'Seni Budaya dan Keterampilan', 'short_name' => 'Seni Budaya', 'category' => 'Keterampilan'],
            ['code' => 'PKWU', 'name' => 'Prakarya dan Kewirausahaan', 'short_name' => 'Prakarya', 'category' => 'Keterampilan'],
        ];

        $subjects = [];

        foreach ($definitions as $index => $definition) {
            $subjects[$definition['code']] = Subject::query()->updateOrCreate(
                ['institution_id' => $institution->getKey(), 'code' => $definition['code']],
                [...$definition, 'display_order' => $index + 1, 'active' => true],
            );
        }

        return $subjects;
    }

    /** @param array<string, AcademicClass> $classes @param array<string, Subject> $subjects @param array<string, User> $users @return array<int, SubjectAssignment> */
    private function seedSubjectAssignments(array $classes, array $subjects, array $users): array
    {
        $teachers = array_values(array_filter($users, fn (User $user): bool => $user->hasRole(RoleCode::Guru)));
        $assignments = [];
        $teacherIndex = 0;

        foreach ($classes as $academicClass) {
            foreach ($subjects as $subject) {
                $teacher = $teachers[$teacherIndex % count($teachers)];
                $assignment = SubjectAssignment::query()->updateOrCreate(
                    ['class_id' => $academicClass->getKey(), 'subject_id' => $subject->getKey()],
                    [
                        'teacher_id' => $teacher->getKey(),
                        'minimum_score' => 70,
                        'display_order' => $subject->display_order,
                        'active' => true,
                    ],
                );

                $assignments[] = $assignment;
                $teacherIndex++;
            }
        }

        return $assignments;
    }

    private function seedInstitutionSettings(Institution $institution): void
    {
        $institution->update([
            'address' => 'Jl. Pendidikan No. 17, Kecamatan Sukamaju, Kota Bandung',
            'phone' => '(022) 7000-1234',
            'email' => 'info@pkbmcontoh.test',
            'npsn' => 'P99887766',
            'permit_number' => '421.9/PKBM/2026/017',
            'postal_code' => '40123',
        ]);

        app(InstitutionFormatSettingsService::class)->updateForInstitution($institution, [
            'header_text' => 'PUSAT KEGIATAN BELAJAR MASYARAKAT (PKBM) CONTOH',
            'footer_text' => null,
            'show_logo' => false,
        ]);

        $ranges = [
            ['code' => 'A', 'label' => 'Sangat Baik', 'minimum_score' => 86, 'maximum_score' => 100, 'display_order' => 1],
            ['code' => 'B', 'label' => 'Baik', 'minimum_score' => 71, 'maximum_score' => 85, 'display_order' => 2],
            ['code' => 'C', 'label' => 'Cukup', 'minimum_score' => 56, 'maximum_score' => 70, 'display_order' => 3],
            ['code' => 'D', 'label' => 'Perlu Bimbingan', 'minimum_score' => 0, 'maximum_score' => 55, 'display_order' => 4],
        ];

        foreach ($ranges as $range) {
            PredicateRange::query()->updateOrCreate(
                ['institution_id' => $institution->getKey(), 'code' => $range['code']],
                [...$range, 'active' => true],
            );
        }
    }

    /** @param array<string, Subject> $subjects @param array<string, User> $users @return array<int, DescriptionSnippet> */
    private function seedDescriptionSnippets(Institution $institution, array $subjects, array $users): array
    {
        $definitions = [
            'PAI' => [
                'material' => 'nilai keagamaan, ibadah, dan akhlak mulia dalam kehidupan sehari-hari',
                'content' => 'Menunjukkan sikap santun, bertanggung jawab, dan mampu menerapkan nilai keagamaan dalam keseharian.',
            ],
            'PPKN' => [
                'material' => 'nilai Pancasila, hak dan kewajiban warga negara, serta gotong royong',
                'content' => 'Memahami nilai Pancasila dan menunjukkan perilaku gotong royong dalam kegiatan pembelajaran.',
            ],
            'BIN' => [
                'material' => 'membaca pemahaman, menyusun paragraf efektif, dan komunikasi lisan',
                'content' => 'Mampu memahami bacaan dan menyampaikan gagasan secara lisan maupun tulisan dengan baik.',
            ],
            'MAT' => [
                'material' => 'operasi bilangan, aljabar dasar, dan pemecahan masalah kontekstual',
                'content' => 'Mampu menyelesaikan permasalahan matematika menggunakan strategi yang tepat.',
            ],
            'IPA' => [
                'material' => 'pengamatan gejala alam, ekosistem, dan penerapan konsep sains sederhana',
                'content' => 'Mampu mengamati gejala alam dan menjelaskan hasil pengamatan dengan bahasa sederhana.',
            ],
            'IPS' => [
                'material' => 'interaksi sosial, sejarah lokal, dan kegiatan ekonomi masyarakat',
                'content' => 'Memahami lingkungan sosial dan mampu menghubungkan materi dengan kehidupan sehari-hari.',
            ],
            'BIG' => [
                'material' => 'kosakata fungsional dan ungkapan komunikasi sederhana dalam bahasa Inggris',
                'content' => 'Mampu memahami kosakata dan menggunakan ungkapan sederhana dalam Bahasa Inggris.',
            ],
            'PJOK' => [
                'material' => 'kebugaran jasmani, permainan bola besar, dan pola hidup sehat',
                'content' => 'Berpartisipasi aktif dan menunjukkan sportivitas dalam kegiatan jasmani.',
            ],
            'SBK' => [
                'material' => 'ekspresi karya seni rupa dan apresiasi budaya daerah',
                'content' => 'Mampu mengekspresikan ide kreatif melalui karya seni dengan percaya diri.',
            ],
            'PKWU' => [
                'material' => 'perancangan produk kerajinan dan dasar kewirausahaan mandiri',
                'content' => 'Mampu merancang karya sederhana dan menunjukkan kemandirian dalam proses berkarya.',
            ],
        ];

        $predicateRanges = PredicateRange::query()
            ->where('institution_id', $institution->getKey())
            ->where('active', true)
            ->orderBy('display_order')
            ->get();

        $snippetsBySubject = [];

        foreach ($subjects as $code => $subject) {
            $snippet = DescriptionSnippet::query()->updateOrCreate(
                [
                    'institution_id' => $institution->getKey(),
                    'subject_id' => $subject->getKey(),
                    'title' => 'Capaian '.$subject->short_name,
                ],
                [
                    'owner_user_id' => null,
                    'material' => $definitions[$code]['material'],
                    'content' => $definitions[$code]['content'],
                    'visibility' => DescriptionSnippetVisibility::Shared->value,
                    'active' => true,
                ],
            );

            foreach ($predicateRanges as $range) {
                DescriptionSnippetTemplate::query()->updateOrCreate(
                    [
                        'description_snippet_id' => $snippet->getKey(),
                        'predicate_code' => $range->code,
                    ],
                    [
                        'predicate_label' => $range->label,
                        'template' => match ($range->code) {
                            'A' => 'Peserta didik menunjukkan penguasaan yang sangat baik dalam {material}.',
                            'B' => 'Peserta didik menunjukkan penguasaan yang baik dalam {material}.',
                            'C' => 'Peserta didik cukup memahami {material} dan perlu latihan lebih konsisten.',
                            default => 'Peserta didik perlu bimbingan lebih lanjut dalam {material}.',
                        },
                        'active' => true,
                    ],
                );
            }

            $snippetsBySubject[$subject->getKey()] = $snippet;
        }

        DescriptionSnippet::query()->updateOrCreate(
            [
                'institution_id' => $institution->getKey(),
                'owner_user_id' => $users['guru']->getKey(),
                'title' => 'Catatan perkembangan pribadi',
            ],
            [
                'subject_id' => null,
                'material' => 'kemandirian belajar dan partisipasi aktif di kelas',
                'content' => 'Menunjukkan perkembangan yang positif dan perlu mempertahankan konsistensi belajar.',
                'visibility' => DescriptionSnippetVisibility::Private->value,
                'active' => true,
            ],
        );

        return $snippetsBySubject;
    }

    /** @param array<int, SubjectAssignment> $assignments @param array<int, DescriptionSnippet> $snippets */
    private function seedGradeEntryContexts(Institution $institution, Semester $semester, array $assignments, array $snippets): void
    {
        foreach ($assignments as $assignment) {
            $snippet = $snippets[$assignment->subject_id] ?? null;

            GradeEntryContext::query()->updateOrCreate(
                [
                    'semester_id' => $semester->getKey(),
                    'subject_assignment_id' => $assignment->getKey(),
                ],
                [
                    'institution_id' => $institution->getKey(),
                    'description_snippet_id' => $snippet?->getKey(),
                    'material' => $snippet?->material,
                ],
            );
        }
    }

    /** @return array<string, Student> */
    private function seedStudents(Institution $institution): array
    {
        $religions = ['Islam', 'Islam', 'Islam', 'Kristen', 'Katolik', 'Islam', 'Hindu', 'Islam', 'Buddha', 'Islam'];
        $livingWithOptions = ['Orang Tua', 'Orang Tua', 'Orang Tua', 'Wali', 'Mandiri'];
        $transportOptions = ['Jalan Kaki', 'Sepeda Motor', 'Angkutan Umum', 'Sepeda'];
        $educations = ['SD/Sederajat', 'SMP/Sederajat', 'SMA/SMK', 'D3', 'S1'];
        $occupations = ['Wiraswasta', 'Buruh Harian', 'Pedagang', 'Karyawan Swasta', 'Petani', 'Ibu Rumah Tangga', 'Penjahit'];
        $incomes = ['< Rp 1.000.000', 'Rp 1.000.000 - Rp 2.500.000', 'Rp 2.500.000 - Rp 5.000.000'];

        $definitions = [
            ['number' => 'PD-26001', 'name' => 'Ahmad Fauzan', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2013-02-12', 'guardian' => 'Dedi Fauzan', 'class' => 'PA-1', 'status' => 'ACTIVE'],
            ['number' => 'PD-26002', 'name' => 'Aisyah Putri', 'gender' => 'P', 'birth_place' => 'Cimahi', 'birth_date' => '2013-06-20', 'guardian' => 'Rina Sari', 'class' => 'PA-1', 'status' => 'ACTIVE'],
            ['number' => 'PD-26003', 'name' => 'Bagas Ramadhan', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2012-10-08', 'guardian' => 'Agus Ramadhan', 'class' => 'PA-1', 'status' => 'ACTIVE'],
            ['number' => 'PD-26004', 'name' => 'Citra Lestari', 'gender' => 'P', 'birth_place' => 'Sumedang', 'birth_date' => '2013-01-30', 'guardian' => 'Dian Lestari', 'class' => 'PA-1', 'status' => 'ACTIVE'],
            ['number' => 'PD-26005', 'name' => 'Deni Kurniawan', 'gender' => 'L', 'birth_place' => 'Garut', 'birth_date' => '2012-11-17', 'guardian' => 'Ujang Kurniawan', 'class' => 'PA-6', 'status' => 'ACTIVE'],
            ['number' => 'PD-26006', 'name' => 'Eka Maharani', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2010-03-14', 'guardian' => 'Yani Maharani', 'class' => 'PB-7', 'status' => 'ACTIVE'],
            ['number' => 'PD-26007', 'name' => 'Fajar Nugraha', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2010-08-22', 'guardian' => 'Nana Nugraha', 'class' => 'PB-7', 'status' => 'ACTIVE'],
            ['number' => 'PD-26008', 'name' => 'Galuh Permata', 'gender' => 'P', 'birth_place' => 'Tasikmalaya', 'birth_date' => '2010-12-05', 'guardian' => 'Sari Permata', 'class' => 'PB-7', 'status' => 'ACTIVE'],
            ['number' => 'PD-26009', 'name' => 'Hendra Wijaya', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2010-05-19', 'guardian' => 'Bambang Wijaya', 'class' => 'PB-7', 'status' => 'ACTIVE'],
            ['number' => 'PD-26010', 'name' => 'Intan Safitri', 'gender' => 'P', 'birth_place' => 'Cianjur', 'birth_date' => '2010-09-11', 'guardian' => 'Euis Safitri', 'class' => 'PB-7', 'status' => 'ACTIVE'],
            ['number' => 'PD-26011', 'name' => 'Joko Susilo', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2009-04-07', 'guardian' => 'Slamet Susilo', 'class' => 'PB-8', 'status' => 'ACTIVE', 'from_closed' => 'PB-7-25', 'decision' => 'PROMOTE'],
            ['number' => 'PD-26012', 'name' => 'Kartika Sari', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2009-07-16', 'guardian' => 'Wati Sari', 'class' => 'PB-8', 'status' => 'ACTIVE', 'from_closed' => 'PB-7-25', 'decision' => 'PROMOTE'],
            ['number' => 'PD-26013', 'name' => 'Lukman Hakim', 'gender' => 'L', 'birth_place' => 'Subang', 'birth_date' => '2009-02-25', 'guardian' => 'Hasan Hakim', 'class' => 'PB-8', 'status' => 'ACTIVE'],
            ['number' => 'PD-26014', 'name' => 'Mila Anggraini', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2009-10-29', 'guardian' => 'Nining Anggraini', 'class' => 'PB-9', 'status' => 'ACTIVE'],
            ['number' => 'PD-26015', 'name' => 'Naufal Ardiansyah', 'gender' => 'L', 'birth_place' => 'Bogor', 'birth_date' => '2009-06-13', 'guardian' => 'Arif Ardiansyah', 'class' => 'PB-8', 'status' => 'ACTIVE', 'transferred' => true],
            ['number' => 'PD-26016', 'name' => 'Oki Setiawan', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2007-01-18', 'guardian' => 'Tono Setiawan', 'class' => 'PC-10', 'status' => 'ACTIVE'],
            ['number' => 'PD-26017', 'name' => 'Putri Amelia', 'gender' => 'P', 'birth_place' => 'Cimahi', 'birth_date' => '2007-05-23', 'guardian' => 'Lilis Amelia', 'class' => 'PC-10', 'status' => 'ACTIVE'],
            ['number' => 'PD-26018', 'name' => 'Qori Azzahra', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2007-09-09', 'guardian' => 'Yusuf Azzahra', 'class' => 'PC-10', 'status' => 'ACTIVE'],
            ['number' => 'PD-26019', 'name' => 'Raka Aditya', 'gender' => 'L', 'birth_place' => 'Garut', 'birth_date' => '2007-11-02', 'guardian' => 'Dadan Aditya', 'class' => 'PC-12', 'status' => 'ACTIVE'],
            ['number' => 'PD-26020', 'name' => 'Salsa Nirmala', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2007-03-27', 'guardian' => 'Novi Nirmala', 'class' => 'PC-12', 'status' => 'ACTIVE'],
            // Alumni & status non-aktif untuk kelengkapan Buku Induk & riwayat periode
            ['number' => 'PD-25001', 'name' => 'Taufik Hidayat', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2011-04-12', 'guardian' => 'Rahmat Hidayat', 'class' => null, 'status' => 'GRADUATED', 'from_closed' => 'PA-6-25', 'decision' => 'GRADUATE', 'diploma' => 'DN-02/PA/2026/001'],
            ['number' => 'PD-25002', 'name' => 'Umi Kalsum', 'gender' => 'P', 'birth_place' => 'Cimahi', 'birth_date' => '2008-08-19', 'guardian' => 'Hendra Kalsum', 'class' => null, 'status' => 'GRADUATED', 'from_closed' => 'PB-9-25', 'decision' => 'GRADUATE', 'diploma' => 'DN-02/PB/2026/002'],
            ['number' => 'PD-25003', 'name' => 'Vino Bastian', 'gender' => 'L', 'birth_place' => 'Garut', 'birth_date' => '2005-12-03', 'guardian' => 'Jajang Bastian', 'class' => null, 'status' => 'GRADUATED', 'from_closed' => 'PC-12-25', 'decision' => 'GRADUATE', 'diploma' => 'DN-02/PC/2026/003'],
            ['number' => 'PD-25004', 'name' => 'Wulan Dari', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2009-09-14', 'guardian' => 'Sutrisno', 'class' => null, 'status' => 'INACTIVE'],
            ['number' => 'PD-25005', 'name' => 'Yogi Pratama', 'gender' => 'L', 'birth_place' => 'Sumedang', 'birth_date' => '2008-01-21', 'guardian' => 'Asep Pratama', 'class' => null, 'status' => 'TRANSFERRED'],
        ];

        $students = [];

        foreach ($definitions as $index => $definition) {
            $seq = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $birthDate = Carbon::parse($definition['birth_date']);
            $nikDate = $birthDate->format('dmy');
            $firstName = Str::before($definition['name'], ' ');
            $lastName = Str::after($definition['name'], ' ');
            $programPrefix = $definition['class'] ?? $definition['from_closed'] ?? 'PB';

            $previousSchool = match (true) {
                str_starts_with($programPrefix, 'PA') => 'PAUD Tunas Harapan',
                str_starts_with($programPrefix, 'PB') => 'SDN Sukamaju '.$seq,
                default => 'SMPN 12 Bandung',
            };

            $student = Student::query()->updateOrCreate(
                ['institution_id' => $institution->getKey(), 'student_number' => $definition['number']],
                [
                    'national_student_number' => '00'.substr($definition['number'], -5).$seq,
                    'nik' => '327301'.$nikDate.'00'.$seq,
                    'family_card_number' => '32730115051200'.$seq,
                    'full_name' => $definition['name'],
                    'nickname' => $firstName,
                    'gender' => $definition['gender'],
                    'birth_place' => $definition['birth_place'],
                    'birth_date' => $definition['birth_date'],
                    'religion' => $religions[$index % count($religions)],
                    'citizenship' => 'WNI',
                    'birth_order' => ($index % 3) + 1,
                    'siblings_count' => ($index % 3) + 2,
                    'address' => 'Jl. Warga Belajar No. '.$seq.', Kec. Sukamaju, Kota Bandung',
                    'postal_code' => '4012'.($index % 9),
                    'phone_number' => '0813-2200-01'.$seq,
                    'living_with' => $livingWithOptions[$index % count($livingWithOptions)],
                    'transportation_mode' => $transportOptions[$index % count($transportOptions)],
                    'father_name' => $definition['guardian'],
                    'father_nik' => '32730110047800'.$seq,
                    'father_birth_place' => $definition['birth_place'],
                    'father_birth_date' => $birthDate->copy()->subYears(28)->toDateString(),
                    'father_education' => $educations[$index % count($educations)],
                    'father_occupation' => $occupations[$index % 5],
                    'father_income' => $incomes[$index % count($incomes)],
                    'father_phone' => '0812-2100-01'.$seq,
                    'mother_name' => 'Ibu '.$lastName,
                    'mother_nik' => '32730152088000'.$seq,
                    'mother_birth_place' => 'Bandung',
                    'mother_birth_date' => $birthDate->copy()->subYears(25)->toDateString(),
                    'mother_education' => $educations[($index + 1) % count($educations)],
                    'mother_occupation' => $occupations[5 + ($index % 2)],
                    'mother_income' => $incomes[0],
                    'mother_phone' => '0812-2100-02'.$seq,
                    'guardian_name' => $definition['guardian'],
                    'guardian_relation' => 'Orang Tua',
                    'guardian_occupation' => $occupations[$index % 5],
                    'guardian_phone' => '0812-2100-01'.$seq,
                    'previous_school_name' => $previousSchool,
                    'previous_diploma_number' => str_starts_with($programPrefix, 'PA') ? null : 'DN-02/2024/00'.$seq,
                    'entry_date' => isset($definition['from_closed']) || $definition['status'] !== 'ACTIVE' ? '2025-07-01' : '2026-07-01',
                    'status' => $definition['status'],
                    'graduation_certificate_number' => $definition['diploma'] ?? null,
                ],
            );

            $students[$definition['number']] = $student;
        }

        return $students;
    }

    /**
     * @param  array{closedYear: AcademicYear, closedGenap: Semester, activeYear: AcademicYear, activeSemester: Semester}  $periods
     * @param  array<string, AcademicClass>  $closedClasses
     * @param  array<string, AcademicClass>  $activeClasses
     * @param  array<string, Student>  $students
     * @param  array<int, SubjectAssignment>  $closedAssignments
     * @param  array<string, Subject>  $subjects
     * @param  array<string, User>  $users
     * @return array<string, Enrollment>
     */
    private function seedClosedYearEnrollmentsAndDecisions(
        Institution $institution,
        array $periods,
        array $closedClasses,
        array $activeClasses,
        array $students,
        array $closedAssignments,
        array $subjects,
        array $users,
    ): array {
        $closedYear = $periods['closedYear'];
        $closedGenap = $periods['closedGenap'];
        $activeYear = $periods['activeYear'];
        $activeSemester = $periods['activeSemester'];

        $closedMappings = [
            'PD-26011' => ['class' => 'PB-7-25', 'decision' => 'PROMOTE', 'label' => 'Naik ke tingkat 8', 'enrollment_status' => 'COMPLETED', 'target_class' => 'PB-8', 'continuation' => 'CONTINUE'],
            'PD-26012' => ['class' => 'PB-7-25', 'decision' => 'PROMOTE', 'label' => 'Naik ke tingkat 8', 'enrollment_status' => 'COMPLETED', 'target_class' => 'PB-8', 'continuation' => 'CONTINUE'],
            'PD-25001' => ['class' => 'PA-6-25', 'decision' => 'GRADUATE', 'label' => 'Lulus Paket A', 'enrollment_status' => 'GRADUATED', 'target_class' => null, 'continuation' => 'GRADUATED'],
            'PD-25002' => ['class' => 'PB-9-25', 'decision' => 'GRADUATE', 'label' => 'Lulus Paket B', 'enrollment_status' => 'GRADUATED', 'target_class' => null, 'continuation' => 'GRADUATED'],
            'PD-25003' => ['class' => 'PC-12-25', 'decision' => 'GRADUATE', 'label' => 'Lulus Paket C', 'enrollment_status' => 'GRADUATED', 'target_class' => null, 'continuation' => 'GRADUATED'],
        ];

        $closedEnrollments = [];

        foreach ($closedMappings as $studentNumber => $mapping) {
            $student = $students[$studentNumber];
            $academicClass = $closedClasses[$mapping['class']];

            $enrollment = Enrollment::query()->updateOrCreate(
                ['student_id' => $student->getKey(), 'class_id' => $academicClass->getKey(), 'academic_year_id' => $closedYear->getKey()],
                [
                    'start_date' => '2025-07-01',
                    'end_date' => '2026-06-30',
                    'enrollment_status' => $mapping['enrollment_status'],
                    'notes' => 'Riwayat pembelajaran tahun pelajaran 2025/2026.',
                ],
            );

            $enrollment->forceFill(['reporting_enrollment_id' => $enrollment->getKey()])->saveQuietly();
            $closedEnrollments[$studentNumber] = $enrollment;

            AcademicDecision::query()->updateOrCreate(
                ['student_id' => $student->getKey(), 'academic_year_id' => $closedYear->getKey()],
                [
                    'semester_id' => $closedGenap->getKey(),
                    'enrollment_id' => $enrollment->getKey(),
                    'class_id' => $academicClass->getKey(),
                    'decided_by' => $users['guru']->getKey(),
                    'decision' => $mapping['decision'],
                    'label_snapshot' => $mapping['label'],
                    'revision' => 1,
                    'decided_at' => Carbon::create(2026, 6, 20, 10),
                ],
            );

            AcademicDecisionBatch::query()->updateOrCreate(
                ['academic_year_id' => $closedYear->getKey(), 'class_id' => $academicClass->getKey()],
                [
                    'institution_id' => $institution->getKey(),
                    'semester_id' => $closedGenap->getKey(),
                    'status' => 'FINALIZED',
                    'finalized_by' => $users['guru']->getKey(),
                    'finalized_at' => Carbon::create(2026, 6, 22, 14),
                    'revision' => 1,
                ],
            );
        }

        $this->seedReportCards(
            $institution,
            $closedGenap,
            array_values($closedEnrollments),
            $closedAssignments,
            $subjects,
            $users,
            ['LOCKED'],
            Carbon::create(2026, 6, 25),
        );

        $preparation = PeriodPreparation::query()->updateOrCreate(
            ['source_year_id' => $closedYear->getKey(), 'kind' => 'YEAR'],
            [
                'source_semester_id' => $closedGenap->getKey(),
                'target_year_id' => $activeYear->getKey(),
                'target_semester_id' => $activeSemester->getKey(),
                'prepared_by' => $users['admin']->getKey(),
                'status' => 'COMPLETED',
                'revision' => 2,
                'configuration_reviewed' => true,
                'review_hash' => hash('sha256', 'demo-2025-2026-to-2026-2027'),
                'completed_at' => Carbon::create(2026, 6, 29, 15),
            ],
        );

        foreach ($closedMappings as $studentNumber => $mapping) {
            $enrollment = $closedEnrollments[$studentNumber];
            PeriodPlacement::query()->updateOrCreate(
                ['period_preparation_id' => $preparation->getKey(), 'student_id' => $enrollment->student_id],
                [
                    'enrollment_id' => $enrollment->getKey(),
                    'class_id' => $mapping['target_class'] ? $activeClasses[$mapping['target_class']]->getKey() : null,
                    'continuation' => $mapping['continuation'],
                ],
            );
        }

        return $closedEnrollments;
    }

    /**
     * @param  array<string, AcademicClass>  $classes
     * @param  array<string, Student>  $students
     * @param  array<string, Enrollment>  $closedEnrollments
     * @return array<int, Enrollment>
     */
    private function seedActiveEnrollments(
        AcademicYear $academicYear,
        array $classes,
        array $students,
        User $admin,
        array $closedEnrollments,
    ): array {
        $classMap = [
            'PD-26001' => 'PA-1',
            'PD-26002' => 'PA-1',
            'PD-26003' => 'PA-1',
            'PD-26004' => 'PA-1',
            'PD-26005' => 'PA-6',
            'PD-26006' => 'PB-7',
            'PD-26007' => 'PB-7',
            'PD-26008' => 'PB-7',
            'PD-26009' => 'PB-7',
            'PD-26010' => 'PB-7',
            'PD-26011' => 'PB-8',
            'PD-26012' => 'PB-8',
            'PD-26013' => 'PB-8',
            'PD-26014' => 'PB-9',
            'PD-26015' => 'PB-8',
            'PD-26016' => 'PC-10',
            'PD-26017' => 'PC-10',
            'PD-26018' => 'PC-10',
            'PD-26019' => 'PC-12',
            'PD-26020' => 'PC-12',
        ];

        $enrollments = [];

        foreach ($classMap as $studentNumber => $classCode) {
            $student = $students[$studentNumber];
            $isTransferred = $studentNumber === 'PD-26015';

            if ($isTransferred) {
                $source = Enrollment::query()->updateOrCreate(
                    ['student_id' => $student->getKey(), 'class_id' => $classes['PB-7']->getKey(), 'academic_year_id' => $academicYear->getKey()],
                    [
                        'start_date' => '2026-07-01',
                        'end_date' => '2026-09-15',
                        'enrollment_status' => 'TRANSFERRED',
                        'notes' => 'Pindah kelas berdasarkan penyesuaian tingkat belajar.',
                    ],
                );
                $source->forceFill(['reporting_enrollment_id' => $source->getKey()])->saveQuietly();
            }

            $enrollment = Enrollment::query()->updateOrCreate(
                ['student_id' => $student->getKey(), 'class_id' => $classes[$classCode]->getKey(), 'academic_year_id' => $academicYear->getKey()],
                [
                    'start_date' => $isTransferred ? '2026-09-16' : '2026-07-01',
                    'end_date' => null,
                    'enrollment_status' => 'ACTIVE',
                    'notes' => $isTransferred
                        ? 'Enrollment aktif setelah pindah kelas.'
                        : (isset($closedEnrollments[$studentNumber]) ? 'Naik kelas dari tahun pelajaran 2025/2026.' : null),
                ],
            );

            $enrollment->forceFill(['reporting_enrollment_id' => $enrollment->getKey()])->saveQuietly();

            if ($isTransferred && isset($source)) {
                ClassTransfer::query()->firstOrCreate(
                    [
                        'student_id' => $student->getKey(),
                        'from_enrollment_id' => $source->getKey(),
                        'to_enrollment_id' => $enrollment->getKey(),
                    ],
                    [
                        'effective_date' => '2026-09-16',
                        'reason' => 'Penyesuaian tingkat belajar',
                        'notes' => 'Data dummy untuk simulasi histori perpindahan kelas.',
                        'approved_by_admin_id' => $admin->getKey(),
                    ],
                );

                unset($source);
            }

            $enrollments[] = $enrollment;
        }

        return $enrollments;
    }

    /**
     * @param  array<int, Enrollment>  $enrollments
     * @param  array<int, SubjectAssignment>  $assignments
     * @param  array<string, Subject>  $subjects
     * @param  array<string, User>  $users
     * @param  array<int, string>  $statuses
     */
    private function seedReportCards(
        Institution $institution,
        Semester $semester,
        array $enrollments,
        array $assignments,
        array $subjects,
        array $users,
        array $statuses,
        Carbon $issuedDate,
    ): void {
        $predicateRanges = PredicateRange::query()->where('institution_id', $institution->getKey())->where('active', true)->orderBy('display_order')->get();
        $rangesSnapshot = $predicateRanges->map(fn (PredicateRange $range): array => $range->toSnapshot())->all();
        $snippets = DescriptionSnippet::query()->where('institution_id', $institution->getKey())->whereNotNull('subject_id')->get()->keyBy('subject_id');
        $assignmentsByClass = collect($assignments)->groupBy('class_id');
        $identity = app(ReportCardIdentityService::class);
        $yearSlug = str_replace('/', '-', $semester->academicYear->name);
        $extracurricularPool = [
            ['activity' => 'Pramuka', 'description' => 'Aktif mengikuti kegiatan kepramukaan dan menunjukkan kerja sama yang baik.'],
            ['activity' => 'Keterampilan Komputer', 'description' => 'Mampu mengoperasikan aplikasi perkantoran dasar dengan cukup terampil.'],
            ['activity' => 'Kewirausahaan', 'description' => 'Antusias mengikuti praktik pembuatan produk kreatif dan pemasaran sederhana.'],
            ['activity' => 'Seni & Budaya', 'description' => 'Berpartisipasi aktif dalam kegiatan seni dan penampilan kelompok.'],
        ];

        foreach ($enrollments as $index => $enrollment) {
            $status = $statuses[$index % count($statuses)];
            $teacher = $enrollment->academicClass->homeroom_teacher_id
                ? User::query()->findOrFail($enrollment->academicClass->homeroom_teacher_id)
                : $users['guru'];
            $submittedAt = in_array($status, ['SUBMITTED', 'IN_REVIEW', 'NEEDS_REVISION', 'APPROVED', 'LOCKED'], true) ? $issuedDate->copy()->subDays(7)->setHour(9) : null;
            $reviewedAt = in_array($status, ['IN_REVIEW', 'NEEDS_REVISION', 'APPROVED', 'LOCKED'], true) ? $issuedDate->copy()->subDays(5)->setHour(10) : null;
            $rejectedAt = $status === 'NEEDS_REVISION' ? $issuedDate->copy()->subDays(4)->setHour(11) : null;
            $approvedAt = in_array($status, ['APPROVED', 'LOCKED'], true) ? $issuedDate->copy()->subDays(2)->setHour(13) : null;
            $lockedAt = $status === 'LOCKED' ? $issuedDate->copy()->setHour(14) : null;

            $reportCard = ReportCard::query()->updateOrCreate(
                ['enrollment_id' => $enrollment->getKey(), 'semester_id' => $semester->getKey()],
                [
                    'document_number' => 'RAPOR/'.$yearSlug.'/'.$semester->code->value.'/'.$enrollment->student->student_number,
                    'status' => $status,
                    'issued_date' => $issuedDate->toDateString(),
                    'submitted_at' => $submittedAt,
                    'submitted_by_user_id' => $submittedAt === null ? null : $teacher->getKey(),
                    'reviewed_at' => $reviewedAt,
                    'reviewed_by_user_id' => $reviewedAt === null ? null : $users['kepsek']->getKey(),
                    'rejected_at' => $rejectedAt,
                    'rejected_by_user_id' => $rejectedAt === null ? null : $users['kepsek']->getKey(),
                    'rejection_reason' => $status === 'NEEDS_REVISION' ? 'Mohon lengkapi catatan perkembangan peserta didik.' : null,
                    'approved_at' => $approvedAt,
                    'approved_by_user_id' => $approvedAt === null ? null : $users['kepsek']->getKey(),
                    'locked_at' => $lockedAt,
                    'locked_by_user_id' => $lockedAt === null ? null : $users['kepsek']->getKey(),
                    'unlocked_at' => null,
                    'unlocked_by_user_id' => null,
                    'unlock_reason' => null,
                ],
            );

            foreach ($assignmentsByClass->get($enrollment->class_id, collect()) as $assignment) {
                $score = 72 + (($index * 3 + $assignment->display_order) % 25);
                $predicate = $predicateRanges->first(fn (PredicateRange $range): bool => $range->contains($score));
                $subject = $subjects[$assignment->subject->code];

                ReportGrade::withoutEvents(function () use ($reportCard, $assignment, $subject, $score, $predicate, $rangesSnapshot, $snippets): void {
                    ReportGrade::query()->updateOrCreate(
                        ['report_card_id' => $reportCard->getKey(), 'subject_assignment_id' => $assignment->getKey()],
                        [
                            'description_snippet_id' => $snippets->get($subject->getKey())?->getKey(),
                            'subject_name_snapshot' => $subject->name,
                            'subject_short_name_snapshot' => $subject->short_name,
                            'minimum_score_snapshot' => $assignment->minimum_score,
                            'predicate_snapshot' => $predicate?->code,
                            'predicate_ranges_snapshot' => $rangesSnapshot,
                            'display_order_snapshot' => $assignment->display_order,
                            'score' => $score,
                            'predicate' => $predicate?->code,
                            'description' => $predicate?->code === 'A'
                                ? 'Menunjukkan penguasaan materi yang sangat baik dan konsisten.'
                                : 'Menunjukkan perkembangan belajar yang baik dan perlu dipertahankan.',
                            'status' => 'DRAFT',
                        ],
                    );
                });
            }

            AttitudeAssessment::query()->updateOrCreate(
                ['report_card_id' => $reportCard->getKey(), 'attitude_type' => 'SPIRITUAL'],
                ['description' => 'Menunjukkan sikap santun, disiplin, dan bertanggung jawab dalam kegiatan belajar.'],
            );
            AttitudeAssessment::query()->updateOrCreate(
                ['report_card_id' => $reportCard->getKey(), 'attitude_type' => 'SOCIAL'],
                ['description' => 'Mampu bekerja sama, menghargai teman, dan berkomunikasi dengan baik.'],
            );
            AttendanceSummary::query()->updateOrCreate(
                ['report_card_id' => $reportCard->getKey()],
                ['sick_days' => $index % 3, 'leave_days' => ($index + 1) % 2, 'unexcused_days' => $index % 2],
            );

            $primaryExtra = $extracurricularPool[$index % count($extracurricularPool)];
            ReportExtracurricular::query()->updateOrCreate(
                ['report_card_id' => $reportCard->getKey(), 'activity_name' => $primaryExtra['activity']],
                ['description' => $primaryExtra['description'], 'display_order' => 1],
            );

            if ($index % 2 === 0) {
                $secondaryExtra = $extracurricularPool[($index + 1) % count($extracurricularPool)];
                ReportExtracurricular::query()->updateOrCreate(
                    ['report_card_id' => $reportCard->getKey(), 'activity_name' => $secondaryExtra['activity']],
                    ['description' => $secondaryExtra['description'], 'display_order' => 2],
                );
            }

            HomeroomNote::query()->updateOrCreate(
                ['report_card_id' => $reportCard->getKey()],
                ['note' => 'Pertahankan semangat belajar dan kembangkan potensi yang dimiliki.'],
            );

            if ($status === 'LOCKED') {
                $reportCard->forceFill(['identity_snapshot' => $identity->snapshot($reportCard)])->saveQuietly();
                $this->seedSignatures($reportCard, $teacher, $users['kepsek'], $issuedDate);
            }

            AuditLog::query()->firstOrCreate(
                [
                    'institution_id' => $institution->getKey(),
                    'report_card_id' => $reportCard->getKey(),
                    'action' => 'demo.report_card_seeded',
                    'entity_type' => ReportCard::class,
                    'entity_id' => $reportCard->getKey(),
                ],
                [
                    'user_id' => $users['admin']->getKey(),
                    'new_value_json' => ['status' => $status],
                    'reason' => 'Data dummy aplikasi.',
                ],
            );
        }
    }

    private function seedSignatures(ReportCard $reportCard, User $teacher, User $kepsek, Carbon $issuedDate): void
    {
        $signatures = [
            [
                'signer_type' => ReportSignerType::HomeroomTeacher->value,
                'user_id' => $teacher->getKey(),
                'signer_name_snapshot' => $teacher->name,
                'signer_title_snapshot' => 'Wali Kelas',
                'signer_employee_number_snapshot' => $teacher->staffProfile?->employee_number,
            ],
            [
                'signer_type' => ReportSignerType::HeadOfPkbm->value,
                'user_id' => $kepsek->getKey(),
                'signer_name_snapshot' => $kepsek->name,
                'signer_title_snapshot' => 'Kepala PKBM',
                'signer_employee_number_snapshot' => $kepsek->staffProfile?->employee_number,
            ],
        ];

        foreach ($signatures as $signature) {
            ReportSignature::query()->updateOrCreate(
                ['report_card_id' => $reportCard->getKey(), 'signer_type' => $signature['signer_type']],
                [...$signature, 'signed_at' => $issuedDate->copy()->setHour(15)],
            );
        }
    }

    /**
     * @param  array<string, AcademicClass>  $classes
     * @param  array<int, SubjectAssignment>  $assignments
     * @param  array<int, Enrollment>  $enrollments
     */
    private function seedGradeImportBatches(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $classes,
        array $assignments,
        array $enrollments,
        User $teacher,
    ): void {
        $pb7 = $classes['PB-7'];
        $pb7Enrollments = collect($enrollments)->where('class_id', $pb7->getKey())->values();
        $pb7Assignment = collect($assignments)->first(fn (SubjectAssignment $item): bool => $item->class_id === $pb7->getKey());

        if (! $pb7Assignment || $pb7Enrollments->isEmpty()) {
            return;
        }

        $previewRows = $pb7Enrollments->map(fn (Enrollment $enrollment, int $idx): array => [
            'student_number' => $enrollment->student->student_number,
            'full_name' => $enrollment->student->full_name,
            'score' => 78 + ($idx * 3),
            'valid' => true,
        ])->all();

        GradeImportBatch::query()->updateOrCreate(
            ['token' => 'demo-import-pb7-'.$pb7Assignment->subject->code],
            [
                'institution_id' => $institution->getKey(),
                'uploaded_by_user_id' => $teacher->getKey(),
                'academic_year_id' => $academicYear->getKey(),
                'semester_id' => $semester->getKey(),
                'academic_class_id' => $pb7->getKey(),
                'subject_assignment_id' => $pb7Assignment->getKey(),
                'file_name' => 'nilai-pb7-'.$pb7Assignment->subject->code.'.xlsx',
                'material' => 'Materi semester ganjil',
                'preview_json' => json_encode($previewRows, JSON_THROW_ON_ERROR),
                'status' => 'IMPORTED',
                'total_rows' => count($previewRows),
                'valid_rows' => count($previewRows),
                'invalid_rows' => 0,
                'imported_rows' => count($previewRows),
                'imported_at' => Carbon::create(2026, 10, 15, 10, 30),
            ],
        );
    }
}
