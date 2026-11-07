<?php

namespace Database\Seeders;

use App\Enums\DescriptionSnippetVisibility;
use App\Enums\ReportSignerType;
use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\AttendanceSummary;
use App\Models\AttitudeAssessment;
use App\Models\AuditLog;
use App\Models\ClassTransfer;
use App\Models\DescriptionSnippet;
use App\Models\Enrollment;
use App\Models\HomeroomAssignment;
use App\Models\HomeroomNote;
use App\Models\Institution;
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

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $institution = Institution::query()->where('name', 'PKBM Contoh')->firstOrFail();
            $users = $this->seedUsers($institution);
            $periods = $this->seedAcademicPeriods($institution);
            $classes = $this->seedClasses($institution, $periods['activeYear'], $users);
            $subjects = $this->seedSubjects($institution);
            $assignments = $this->seedSubjectAssignments($classes, $subjects, $users);
            $this->seedInstitutionSettings($institution);
            $this->seedDescriptionSnippets($institution, $subjects, $users);
            $enrollments = $this->seedStudentsAndEnrollments($institution, $periods['activeYear'], $classes, $users['admin']);
            $this->seedReportCards($institution, $periods['activeSemester'], $enrollments, $assignments, $subjects, $users);
        });
    }

    /** @return array<string, User> */
    private function seedUsers(Institution $institution): array
    {
        $roles = RoleCode::cases();
        $roleModels = collect($roles)->mapWithKeys(function (RoleCode $role): array {
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

    /** @return array{activeYear: AcademicYear, activeSemester: Semester} */
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
        $this->seedSemester($closedYear, 'GENAP', 'Semester Genap', 2, 'CLOSED');
        $this->seedSemester($activeYear, 'GANJIL', 'Semester Ganjil', 1, 'ACTIVE');
        $this->seedSemester($activeYear, 'GENAP', 'Semester Genap', 2, 'DRAFT');

        return [
            'activeYear' => $activeYear,
            'activeSemester' => Semester::query()->where('academic_year_id', $activeYear->getKey())->where('code', 'GANJIL')->firstOrFail(),
        ];
    }

    private function seedSemester(AcademicYear $academicYear, string $code, string $name, int $sequence, string $status): Semester
    {
        return Semester::query()->updateOrCreate(
            ['academic_year_id' => $academicYear->getKey(), 'code' => $code],
            compact('name', 'sequence', 'status'),
        );
    }

    /** @param array<string, User> $users @return array<string, AcademicClass> */
    private function seedClasses(Institution $institution, AcademicYear $academicYear, array $users): array
    {
        $definitions = [
            ['code' => 'PA-1', 'name' => 'Paket A Tingkat 1', 'program' => 'PA', 'level' => 1, 'teacher' => 'guru'],
            ['code' => 'PB-7', 'name' => 'Paket B Tingkat 7A', 'program' => 'PB', 'level' => 7, 'teacher' => 'guru'],
            ['code' => 'PB-8', 'name' => 'Paket B Tingkat 8A', 'program' => 'PB', 'level' => 8, 'teacher' => 'guru'],
            ['code' => 'PC-10', 'name' => 'Paket C Tingkat 10A', 'program' => 'PC', 'level' => 10, 'teacher' => 'guru'],
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
            'footer_text' => 'Dokumen ini diterbitkan melalui Aplikasi e-Rapor PKBM.',
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

    /** @param array<string, Subject> $subjects @param array<string, User> $users */
    private function seedDescriptionSnippets(Institution $institution, array $subjects, array $users): void
    {
        $contents = [
            'PAI' => 'Menunjukkan sikap santun, bertanggung jawab, dan mampu menerapkan nilai keagamaan dalam keseharian.',
            'PPKN' => 'Memahami nilai Pancasila dan menunjukkan perilaku gotong royong dalam kegiatan pembelajaran.',
            'BIN' => 'Mampu memahami bacaan dan menyampaikan gagasan secara lisan maupun tulisan dengan baik.',
            'MAT' => 'Mampu menyelesaikan permasalahan matematika menggunakan strategi yang tepat.',
            'IPA' => 'Mampu mengamati gejala alam dan menjelaskan hasil pengamatan dengan bahasa sederhana.',
            'IPS' => 'Memahami lingkungan sosial dan mampu menghubungkan materi dengan kehidupan sehari-hari.',
            'BIG' => 'Mampu memahami kosakata dan menggunakan ungkapan sederhana dalam Bahasa Inggris.',
            'PJOK' => 'Berpartisipasi aktif dan menunjukkan sportivitas dalam kegiatan jasmani.',
            'SBK' => 'Mampu mengekspresikan ide kreatif melalui karya seni dengan percaya diri.',
            'PKWU' => 'Mampu merancang karya sederhana dan menunjukkan kemandirian dalam proses berkarya.',
        ];

        foreach ($subjects as $code => $subject) {
            DescriptionSnippet::query()->updateOrCreate(
                [
                    'institution_id' => $institution->getKey(),
                    'subject_id' => $subject->getKey(),
                    'title' => 'Capaian '.$subject->short_name,
                ],
                [
                    'owner_user_id' => null,
                    'content' => $contents[$code],
                    'visibility' => DescriptionSnippetVisibility::Shared->value,
                    'active' => true,
                ],
            );
        }

        DescriptionSnippet::query()->updateOrCreate(
            [
                'institution_id' => $institution->getKey(),
                'owner_user_id' => $users['guru']->getKey(),
                'title' => 'Catatan perkembangan pribadi',
            ],
            [
                'subject_id' => null,
                'content' => 'Menunjukkan perkembangan yang positif dan perlu mempertahankan konsistensi belajar.',
                'visibility' => DescriptionSnippetVisibility::Private->value,
                'active' => true,
            ],
        );
    }

    /** @param array<string, AcademicClass> $classes @return array<int, Enrollment> */
    private function seedStudentsAndEnrollments(Institution $institution, AcademicYear $academicYear, array $classes, User $admin): array
    {
        $definitions = [
            ['number' => 'PD-26001', 'name' => 'Ahmad Fauzan', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2013-02-12', 'guardian' => 'Dedi Fauzan', 'class' => 'PA-1'],
            ['number' => 'PD-26002', 'name' => 'Aisyah Putri', 'gender' => 'P', 'birth_place' => 'Cimahi', 'birth_date' => '2013-06-20', 'guardian' => 'Rina Sari', 'class' => 'PA-1'],
            ['number' => 'PD-26003', 'name' => 'Bagas Ramadhan', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2012-10-08', 'guardian' => 'Agus Ramadhan', 'class' => 'PA-1'],
            ['number' => 'PD-26004', 'name' => 'Citra Lestari', 'gender' => 'P', 'birth_place' => 'Sumedang', 'birth_date' => '2013-01-30', 'guardian' => 'Dian Lestari', 'class' => 'PA-1'],
            ['number' => 'PD-26005', 'name' => 'Deni Kurniawan', 'gender' => 'L', 'birth_place' => 'Garut', 'birth_date' => '2012-11-17', 'guardian' => 'Ujang Kurniawan', 'class' => 'PA-1'],
            ['number' => 'PD-26006', 'name' => 'Eka Maharani', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2010-03-14', 'guardian' => 'Yani Maharani', 'class' => 'PB-7'],
            ['number' => 'PD-26007', 'name' => 'Fajar Nugraha', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2010-08-22', 'guardian' => 'Nana Nugraha', 'class' => 'PB-7'],
            ['number' => 'PD-26008', 'name' => 'Galuh Permata', 'gender' => 'P', 'birth_place' => 'Tasikmalaya', 'birth_date' => '2010-12-05', 'guardian' => 'Sari Permata', 'class' => 'PB-7'],
            ['number' => 'PD-26009', 'name' => 'Hendra Wijaya', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2010-05-19', 'guardian' => 'Bambang Wijaya', 'class' => 'PB-7'],
            ['number' => 'PD-26010', 'name' => 'Intan Safitri', 'gender' => 'P', 'birth_place' => 'Cianjur', 'birth_date' => '2010-09-11', 'guardian' => 'Euis Safitri', 'class' => 'PB-7'],
            ['number' => 'PD-26011', 'name' => 'Joko Susilo', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2009-04-07', 'guardian' => 'Slamet Susilo', 'class' => 'PB-8'],
            ['number' => 'PD-26012', 'name' => 'Kartika Sari', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2009-07-16', 'guardian' => 'Wati Sari', 'class' => 'PB-8'],
            ['number' => 'PD-26013', 'name' => 'Lukman Hakim', 'gender' => 'L', 'birth_place' => 'Subang', 'birth_date' => '2009-02-25', 'guardian' => 'Hasan Hakim', 'class' => 'PB-8'],
            ['number' => 'PD-26014', 'name' => 'Mila Anggraini', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2009-10-29', 'guardian' => 'Nining Anggraini', 'class' => 'PB-8'],
            ['number' => 'PD-26015', 'name' => 'Naufal Ardiansyah', 'gender' => 'L', 'birth_place' => 'Bogor', 'birth_date' => '2009-06-13', 'guardian' => 'Arif Ardiansyah', 'class' => 'PB-8', 'transferred' => true],
            ['number' => 'PD-26016', 'name' => 'Oki Setiawan', 'gender' => 'L', 'birth_place' => 'Bandung', 'birth_date' => '2007-01-18', 'guardian' => 'Tono Setiawan', 'class' => 'PC-10'],
            ['number' => 'PD-26017', 'name' => 'Putri Amelia', 'gender' => 'P', 'birth_place' => 'Cimahi', 'birth_date' => '2007-05-23', 'guardian' => 'Lilis Amelia', 'class' => 'PC-10'],
            ['number' => 'PD-26018', 'name' => 'Qori Azzahra', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2007-09-09', 'guardian' => 'Yusuf Azzahra', 'class' => 'PC-10'],
            ['number' => 'PD-26019', 'name' => 'Raka Aditya', 'gender' => 'L', 'birth_place' => 'Garut', 'birth_date' => '2007-11-02', 'guardian' => 'Dadan Aditya', 'class' => 'PC-10'],
            ['number' => 'PD-26020', 'name' => 'Salsa Nirmala', 'gender' => 'P', 'birth_place' => 'Bandung', 'birth_date' => '2007-03-27', 'guardian' => 'Novi Nirmala', 'class' => 'PC-10'],
        ];

        $enrollments = [];

        foreach ($definitions as $definition) {
            $student = Student::query()->updateOrCreate(
                ['institution_id' => $institution->getKey(), 'student_number' => $definition['number']],
                [
                    'national_student_number' => '00'.substr($definition['number'], -5),
                    'full_name' => $definition['name'],
                    'gender' => $definition['gender'],
                    'birth_place' => $definition['birth_place'],
                    'birth_date' => $definition['birth_date'],
                    'address' => 'Jl. Warga Belajar No. '.substr($definition['number'], -2).', Bandung',
                    'guardian_name' => $definition['guardian'],
                    'status' => 'ACTIVE',
                ],
            );

            if ($definition['class'] === 'PB-8' && ($definition['transferred'] ?? false)) {
                $source = Enrollment::query()->updateOrCreate(
                    ['student_id' => $student->getKey(), 'class_id' => $classes['PB-7']->getKey(), 'academic_year_id' => $academicYear->getKey()],
                    [
                        'start_date' => '2026-07-01',
                        'end_date' => '2026-09-15',
                        'enrollment_status' => 'TRANSFERRED',
                        'notes' => 'Pindah kelas berdasarkan penyesuaian tingkat belajar.',
                    ],
                );
            }

            $enrollment = Enrollment::query()->updateOrCreate(
                ['student_id' => $student->getKey(), 'class_id' => $classes[$definition['class']]->getKey(), 'academic_year_id' => $academicYear->getKey()],
                [
                    'start_date' => ($definition['transferred'] ?? false) ? '2026-09-16' : '2026-07-01',
                    'end_date' => null,
                    'enrollment_status' => 'ACTIVE',
                    'notes' => ($definition['transferred'] ?? false) ? 'Enrollment aktif setelah pindah kelas.' : null,
                ],
            );

            if (isset($source)) {
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

    /** @param array<int, Enrollment> $enrollments @param array<int, SubjectAssignment> $assignments @param array<string, Subject> $subjects @param array<string, User> $users */
    private function seedReportCards(Institution $institution, Semester $semester, array $enrollments, array $assignments, array $subjects, array $users): void
    {
        $predicateRanges = PredicateRange::query()->where('institution_id', $institution->getKey())->where('active', true)->orderBy('display_order')->get();
        $rangesSnapshot = $predicateRanges->map(fn (PredicateRange $range): array => $range->toSnapshot())->all();
        $snippets = DescriptionSnippet::query()->where('institution_id', $institution->getKey())->whereNotNull('subject_id')->get()->keyBy('subject_id');
        $assignmentsByClass = collect($assignments)->groupBy('class_id');
        $identity = app(ReportCardIdentityService::class);
        $statuses = ['DRAFT', 'SUBMITTED', 'IN_REVIEW', 'NEEDS_REVISION', 'APPROVED', 'LOCKED'];

        foreach ($enrollments as $index => $enrollment) {
            $status = $statuses[$index % count($statuses)];
            $teacher = $enrollment->academicClass->homeroom_teacher_id
                ? User::query()->findOrFail($enrollment->academicClass->homeroom_teacher_id)
                : $users['guru'];
            $submittedAt = in_array($status, ['SUBMITTED', 'IN_REVIEW', 'NEEDS_REVISION', 'APPROVED', 'LOCKED'], true) ? Carbon::create(2026, 10, 20, 9) : null;
            $reviewedAt = in_array($status, ['IN_REVIEW', 'NEEDS_REVISION', 'APPROVED', 'LOCKED'], true) ? Carbon::create(2026, 10, 22, 10) : null;
            $rejectedAt = $status === 'NEEDS_REVISION' ? Carbon::create(2026, 10, 23, 11) : null;
            $approvedAt = in_array($status, ['APPROVED', 'LOCKED'], true) ? Carbon::create(2026, 10, 25, 13) : null;
            $lockedAt = $status === 'LOCKED' ? Carbon::create(2026, 10, 27, 14) : null;

            $reportCard = ReportCard::query()->updateOrCreate(
                ['enrollment_id' => $enrollment->getKey(), 'semester_id' => $semester->getKey()],
                [
                    'document_number' => 'RAPOR/2026-2027/GANJIL/'.$enrollment->student->student_number,
                    'status' => $status,
                    'issued_date' => '2026-10-27',
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
            ReportExtracurricular::query()->updateOrCreate(
                ['report_card_id' => $reportCard->getKey(), 'activity_name' => 'Pramuka'],
                ['description' => 'Aktif mengikuti kegiatan dan menunjukkan kerja sama yang baik.', 'display_order' => 1],
            );
            HomeroomNote::query()->updateOrCreate(
                ['report_card_id' => $reportCard->getKey()],
                ['note' => 'Pertahankan semangat belajar dan kembangkan potensi yang dimiliki.'],
            );

            if ($status === 'LOCKED') {
                $reportCard->forceFill(['identity_snapshot' => $identity->snapshot($reportCard)])->saveQuietly();
                $this->seedSignatures($reportCard, $teacher, $users['kepsek']);
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

    private function seedSignatures(ReportCard $reportCard, User $teacher, User $kepsek): void
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
                [...$signature, 'signed_at' => Carbon::create(2026, 10, 27, 15)],
            );
        }
    }
}
