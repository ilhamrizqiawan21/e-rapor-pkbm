<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->constrained('academic_classes')->restrictOnDelete();
            $table->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $table->string('decision', 24);
            $table->string('label_snapshot');
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('decided_at');
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id']);
        });
        Schema::create('period_preparations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('source_semester_id')->constrained('semesters')->restrictOnDelete();
            $table->foreignId('target_year_id')->nullable()->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('target_semester_id')->nullable()->constrained('semesters')->restrictOnDelete();
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->string('kind', 16);
            $table->string('status', 16)->default('DRAFT');
            $table->unsignedInteger('revision')->default(1);
            $table->boolean('configuration_reviewed')->default(false);
            $table->string('review_hash', 64)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['source_year_id', 'kind']);
        });
        Schema::create('period_placements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('period_preparation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('academic_classes')->restrictOnDelete();
            $table->string('continuation', 16)->nullable();
            $table->timestamps();
            $table->unique(['period_preparation_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_placements');
        Schema::dropIfExists('period_preparations');
        Schema::dropIfExists('academic_decisions');
    }
};
