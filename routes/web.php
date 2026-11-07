<?php

use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin\AcademicClassController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AuditHistoryController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\ClassTransferController;
use App\Http\Controllers\Admin\DescriptionSnippetController;
use App\Http\Controllers\Admin\DescriptionSnippetPickerController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\GradeEntryController;
use App\Http\Controllers\Admin\GradeImportController;
use App\Http\Controllers\Admin\InstitutionController;
use App\Http\Controllers\Admin\InstitutionFormatSettingsController;
use App\Http\Controllers\Admin\InstitutionPreviewController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\PredicateRangeController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ReportCardAssessmentController;
use App\Http\Controllers\Admin\ReportCardCompletenessController;
use App\Http\Controllers\Admin\ReportCardIdentityController;
use App\Http\Controllers\Admin\ReportCardPdfController;
use App\Http\Controllers\Admin\ReportCardWorkflowController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\SignatorySettingsController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\SubjectAssignmentController;
use App\Http\Controllers\Admin\SubjectAssignmentMappingController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\UserPasswordController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/admin', DashboardController::class)->middleware('role:ADMIN')->name('dashboard.admin');
    Route::get('/dashboard/guru', DashboardController::class)->middleware('role:GURU')->name('dashboard.guru');
    Route::get('/dashboard/kepsek', DashboardController::class)->middleware('role:KEPSEK')->name('dashboard.kepsek');

    Route::get('/account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('password.update');
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('students/import', [StudentImportController::class, 'create'])->name('students.import.create');
        Route::get('students/import/template', [StudentImportController::class, 'template'])->name('students.import.template');
        Route::post('students/import/preview', [StudentImportController::class, 'preview'])->name('students.import.preview');
        Route::post('students/import', [StudentImportController::class, 'store'])->name('students.import.store');
        Route::get('students/import/errors', [StudentImportController::class, 'errorReport'])->name('students.import.errors');
        Route::resource('students', StudentController::class);
        Route::resource('academic-years', AcademicYearController::class);
        Route::resource('academic-years.semesters', SemesterController::class)->except(['show']);
        Route::resource('programs', ProgramController::class);
        Route::resource('programs.levels', LevelController::class);
        Route::resource('academic-classes', AcademicClassController::class);
        Route::resource('academic-classes.enrollments', EnrollmentController::class)->only(['index', 'create', 'store']);
        Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show'])->name('enrollments.show');
        Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'withdraw'])->name('enrollments.withdraw');
        Route::resource('enrollments.transfers', ClassTransferController::class)->only(['index', 'create', 'store']);
        Route::get('academic-classes/subject-assignments/bulk', [SubjectAssignmentMappingController::class, 'bulkCreate'])->name('academic-classes.subject-assignments.bulk-create');
        Route::post('academic-classes/subject-assignments/bulk', [SubjectAssignmentMappingController::class, 'bulkStore'])->name('academic-classes.subject-assignments.bulk-store');
        Route::get('academic-classes/{academic_class}/subject-assignments/copy', [SubjectAssignmentMappingController::class, 'copyCreate'])->name('academic-classes.subject-assignments.copy-create');
        Route::post('academic-classes/{academic_class}/subject-assignments/copy', [SubjectAssignmentMappingController::class, 'copyStore'])->name('academic-classes.subject-assignments.copy-store');
        Route::get('academic-classes/{academic_class}/subject-assignments/reorder', [SubjectAssignmentMappingController::class, 'reorderCreate'])->name('academic-classes.subject-assignments.reorder-create');
        Route::patch('academic-classes/{academic_class}/subject-assignments/reorder', [SubjectAssignmentMappingController::class, 'reorder'])->name('academic-classes.subject-assignments.reorder');
        Route::get('academic-classes/subject-assignments/bulk-teacher', [SubjectAssignmentMappingController::class, 'bulkAssignCreate'])->name('academic-classes.subject-assignments.bulk-assign-teacher.create');
        Route::post('academic-classes/subject-assignments/bulk-teacher', [SubjectAssignmentMappingController::class, 'bulkAssignTeacher'])->name('academic-classes.subject-assignments.bulk-assign-teacher');
        Route::resource('academic-classes.subject-assignments', SubjectAssignmentController::class)->except(['show']);
        Route::get('predicate-ranges/preview', [PredicateRangeController::class, 'preview'])->name('predicate-ranges.preview');
        Route::resource('predicate-ranges', PredicateRangeController::class)->except(['show']);
        Route::resource('subjects', SubjectController::class);
        Route::get('description-snippets/picker', [DescriptionSnippetPickerController::class, 'index'])->name('description-snippets.picker');
        Route::resource('description-snippets', DescriptionSnippetController::class);
        Route::get('grade-entry/import', [GradeImportController::class, 'create'])->name('grade-entry.import.create');
        Route::get('grade-entry/import/template', [GradeImportController::class, 'template'])->name('grade-entry.import.template');
        Route::post('grade-entry/import/preview', [GradeImportController::class, 'preview'])->name('grade-entry.import.preview');
        Route::post('grade-entry/import', [GradeImportController::class, 'store'])->name('grade-entry.import.store');
        Route::get('grade-entry/import/errors', [GradeImportController::class, 'errors'])->name('grade-entry.import.errors');
        Route::get('grade-entry', [GradeEntryController::class, 'index'])->name('grade-entry.index');
        Route::post('grade-entry', [GradeEntryController::class, 'store'])->name('grade-entry.store');
        Route::post('grade-entry/all', [GradeEntryController::class, 'storeAll'])->name('grade-entry.store-all');
        Route::get('report-cards', [ReportCardCompletenessController::class, 'index'])->name('report-cards.index');
        Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::get('ledger/semesters/{semester}/xlsx', [LedgerController::class, 'allExcel'])->name('ledger.all.excel');
        Route::get('ledger/semesters/{semester}/pdf', [LedgerController::class, 'allPdf'])->name('ledger.all.pdf');
        Route::get('ledger/classes/{academicClass}/semesters/{semester}/xlsx', [LedgerController::class, 'classExcel'])->name('ledger.class.excel');
        Route::get('ledger/classes/{academicClass}/semesters/{semester}/pdf', [LedgerController::class, 'classPdf'])->name('ledger.class.pdf');
        Route::get('audit-logs', [AuditHistoryController::class, 'index'])->name('audit-logs.index');
        Route::get('report-cards/{report_card}/identity', [ReportCardIdentityController::class, 'edit'])->name('report-cards.identity.edit');
        Route::put('report-cards/{report_card}/identity', [ReportCardIdentityController::class, 'update'])->name('report-cards.identity.update');
        Route::get('report-cards/{report_card}/pdf/preview', [ReportCardPdfController::class, 'preview'])->name('report-cards.pdf.preview');
        Route::get('report-cards/{report_card}/pdf/download', [ReportCardPdfController::class, 'download'])->name('report-cards.pdf.download');
        Route::get('report-cards/{report_card}/pdf/print', [ReportCardPdfController::class, 'print'])->name('report-cards.pdf.print');
        Route::get('report-cards/{report_card}/pdf/identity', [ReportCardPdfController::class, 'identity'])->name('report-cards.pdf.identity');
        Route::get('report-cards/{report_card}/pdf/grades', [ReportCardPdfController::class, 'grades'])->name('report-cards.pdf.grades');
        Route::get('report-cards/classes/{academicClass}/semesters/{semester}/pdf', [ReportCardPdfController::class, 'batch'])->name('report-cards.pdf.batch');
        Route::get('report-cards/{report_card}/assessments', [ReportCardAssessmentController::class, 'show'])->name('report-cards.assessments.show');
        Route::post('report-cards/{report_card}/assessments/attitude', [ReportCardAssessmentController::class, 'updateAttitude'])->name('report-cards.assessments.attitude');
        Route::post('report-cards/{report_card}/assessments/attendance', [ReportCardAssessmentController::class, 'updateAttendance'])->name('report-cards.assessments.attendance');
        Route::get('report-cards/{report_card}/completeness', [ReportCardCompletenessController::class, 'edit'])->name('report-cards.completeness.edit');
        Route::put('report-cards/{report_card}/extracurriculars', [ReportCardCompletenessController::class, 'updateExtracurriculars'])->name('report-cards.completeness.extracurriculars.update');
        Route::put('report-cards/{report_card}/homeroom-note', [ReportCardCompletenessController::class, 'updateHomeroomNote'])->name('report-cards.completeness.homeroom-note.update');
        Route::post('report-cards/{report_card}/submit', [ReportCardWorkflowController::class, 'submit'])->name('report-cards.submit');
        Route::post('report-cards/{report_card}/review', [ReportCardWorkflowController::class, 'review'])->name('report-cards.review');
        Route::post('report-cards/{report_card}/reject', [ReportCardWorkflowController::class, 'reject'])->name('report-cards.reject');
        Route::post('report-cards/{report_card}/approve', [ReportCardWorkflowController::class, 'approve'])->name('report-cards.approve');
        Route::post('report-cards/{report_card}/lock', [ReportCardWorkflowController::class, 'lock'])->name('report-cards.lock');
        Route::post('report-cards/{report_card}/unlock', [ReportCardWorkflowController::class, 'unlock'])->name('report-cards.unlock');
    });

    Route::middleware('role:ADMIN')->prefix('admin/users/{user}/password')->name('admin.users.password.')->group(function (): void {
        Route::get('/', [UserPasswordController::class, 'edit'])->name('edit');
        Route::put('/', [UserPasswordController::class, 'update'])->name('update');
    });

    Route::middleware('role:ADMIN')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::post('backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
        Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::resource('teachers', TeacherController::class)->parameters(['teachers' => 'user']);
        Route::resource('institutions', InstitutionController::class);
        Route::get('institutions/{institution}/preview', [InstitutionPreviewController::class, 'show'])
            ->name('institutions.preview');
        Route::delete('institutions/{institution}/logo', [InstitutionController::class, 'destroyLogo'])
            ->name('institutions.logo.destroy');

        Route::get('signatory-settings', [SignatorySettingsController::class, 'edit'])
            ->name('signatory-settings.edit');
        Route::put('signatory-settings', [SignatorySettingsController::class, 'update'])
            ->name('signatory-settings.update');

        Route::get('settings/format', [InstitutionFormatSettingsController::class, 'edit'])
            ->name('settings.format.edit');
        Route::put('settings/format', [InstitutionFormatSettingsController::class, 'update'])
            ->name('settings.format.update');
    });
});

Route::view('/components-showcase', 'components-showcase')
    ->middleware(['auth', 'role:ADMIN'])
    ->name('components.showcase');
