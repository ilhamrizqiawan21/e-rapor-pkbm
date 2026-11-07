<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\AttendanceSummary;
use App\Models\AttitudeAssessment;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Level;
use App\Models\Program;
use App\Models\ReportCard;
use App\Models\ReportGrade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicProgressionE2eSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['desktop', 'mobile'] as $device) {
            $institution = Institution::query()->create(['name' => 'PKBM Peralihan '.$device]);
            $admin = User::factory()->withRole(RoleCode::Admin)->create(['institution_id' => $institution->id, 'username' => 'period-admin-'.$device]);
            $teacher = User::factory()->withRole(RoleCode::Guru)->create(['institution_id' => $institution->id, 'username' => 'period-guru-'.$device]);
            $source = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026/2027', 'status' => 'ACTIVE', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
            $semester = $source->semesters()->create(['code' => 'GENAP', 'name' => 'Semester Genap', 'sequence' => 2, 'status' => 'ACTIVE']);
            $target = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2027/2028', 'status' => 'DRAFT', 'start_date' => '2027-07-01', 'end_date' => '2028-06-30']);
            $program = Program::query()->create(['institution_id' => $institution->id, 'name' => 'Paket A', 'code' => 'PA']);
            $one = Level::query()->create(['program_id' => $program->id, 'number' => 1, 'name' => 'Tingkat 1']);
            $two = Level::query()->create(['program_id' => $program->id, 'number' => 2, 'name' => 'Tingkat 2']);
            $class = AcademicClass::query()->create(['academic_year_id' => $source->id, 'program_id' => $program->id, 'level_id' => $one->id, 'code' => 'K1', 'name' => 'Kelas 1 Peralihan', 'homeroom_teacher_id' => $teacher->id]);
            $destination = AcademicClass::query()->create(['academic_year_id' => $target->id, 'program_id' => $program->id, 'level_id' => $two->id, 'code' => 'K2', 'name' => 'Kelas 2 Peralihan', 'homeroom_teacher_id' => $teacher->id]);
            $student = Student::query()->create(['institution_id' => $institution->id, 'full_name' => 'Ahmad Peralihan', 'student_number' => '001']);
            $enrollment = Enrollment::query()->create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $source->id, 'start_date' => '2026-07-01']);
            $enrollment->update(['reporting_enrollment_id' => $enrollment->id]);
            $subject = Subject::query()->create(['institution_id' => $institution->id, 'name' => 'Matematika', 'short_name' => 'MTK', 'code' => 'MAT']);
            $assignment = SubjectAssignment::query()->create(['class_id' => $class->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
            SubjectAssignment::query()->create(['class_id' => $destination->id, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'minimum_score' => 70, 'display_order' => 1, 'active' => true]);
            $report = ReportCard::query()->create(['enrollment_id' => $enrollment->id, 'semester_id' => $semester->id, 'document_number' => 'RAPOR-001', 'status' => 'DRAFT']);
            foreach (['SPIRITUAL', 'SOCIAL'] as $type) {
                AttitudeAssessment::query()->create(['report_card_id' => $report->id, 'attitude_type' => $type, 'description' => 'Baik']);
            }
            AttendanceSummary::query()->create(['report_card_id' => $report->id, 'sick_days' => 0, 'leave_days' => 0, 'unexcused_days' => 0]);
            ReportGrade::query()->create(['report_card_id' => $report->id, 'subject_assignment_id' => $assignment->id,
                'subject_name_snapshot' => 'Matematika', 'subject_short_name_snapshot' => 'MTK', 'minimum_score_snapshot' => 70, 'display_order_snapshot' => 1,
                'score' => 90, 'predicate' => 'A', 'description' => 'Sangat baik memahami materi']);
        }
    }
}
