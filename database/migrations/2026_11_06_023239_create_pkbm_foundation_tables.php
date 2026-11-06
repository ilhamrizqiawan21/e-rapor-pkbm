<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_number')->nullable();
            $table->string('title')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('DRAFT');
            $table->timestamps();
            $table->unique(['institution_id', 'name']);
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedTinyInteger('sequence');
            $table->string('status')->default('DRAFT');
            $table->timestamps();
            $table->unique(['academic_year_id', 'code']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->timestamps();
            $table->unique(['institution_id', 'code']);
        });

        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('name');
            $table->timestamps();
            $table->unique(['program_id', 'number']);
        });

        Schema::create('academic_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('status')->default('ACTIVE');
            $table->timestamps();
            $table->unique(['academic_year_id', 'code']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('student_number');
            $table->string('national_student_number')->nullable();
            $table->string('full_name');
            $table->string('gender', 1)->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->timestamps();
            $table->unique(['institution_id', 'student_number']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('enrollment_status')->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['class_id', 'academic_year_id']);
        });

        Schema::create('class_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->foreignId('to_enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->date('effective_date');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by_admin_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('short_name');
            $table->string('category')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['institution_id', 'code']);
        });

        Schema::create('subject_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('minimum_score')->default(70);
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['class_id', 'subject_id']);
        });

        Schema::create('description_snippets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('visibility')->default('PRIVATE');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('document_number')->nullable();
            $table->string('status')->default('DRAFT');
            $table->date('issued_date')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'semester_id']);
        });

        Schema::create('report_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->restrictOnDelete();
            $table->foreignId('description_snippet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_name_snapshot');
            $table->string('subject_short_name_snapshot');
            $table->unsignedTinyInteger('minimum_score_snapshot');
            $table->unsignedSmallInteger('display_order_snapshot');
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('predicate')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('DRAFT');
            $table->timestamps();
            $table->unique(['report_card_id', 'subject_assignment_id']);
        });

        Schema::create('attitude_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->string('attitude_type');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['report_card_id', 'attitude_type']);
        });

        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sick_days')->default(0);
            $table->unsignedSmallInteger('leave_days')->default(0);
            $table->unsignedSmallInteger('unexcused_days')->default(0);
            $table->timestamps();
        });

        Schema::create('report_extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->string('activity_name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->timestamps();
        });

        Schema::create('homeroom_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('report_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->string('signer_type');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('signer_name_snapshot');
            $table->string('signer_title_snapshot')->nullable();
            $table->string('signature_image_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->unique(['report_card_id', 'signer_type']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_card_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('old_value_json')->nullable();
            $table->json('new_value_json')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('report_signatures');
        Schema::dropIfExists('homeroom_notes');
        Schema::dropIfExists('report_extracurriculars');
        Schema::dropIfExists('attendance_summaries');
        Schema::dropIfExists('attitude_assessments');
        Schema::dropIfExists('report_grades');
        Schema::dropIfExists('report_cards');
        Schema::dropIfExists('description_snippets');
        Schema::dropIfExists('subject_assignments');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('class_transfers');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('students');
        Schema::dropIfExists('academic_classes');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('institution_id');
        });
        Schema::dropIfExists('institutions');
    }
};
