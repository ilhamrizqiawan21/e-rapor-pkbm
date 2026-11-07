<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('file_name')->nullable();
            $table->text('material')->nullable();
            $table->longText('preview_json');
            $table->string('status', 32)->default('PREVIEWED');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'created_at']);
            $table->index(['academic_year_id', 'semester_id', 'academic_class_id', 'subject_assignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_import_batches');
    }
};
