<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_entry_contexts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('description_snippet_id')->nullable()->constrained()->nullOnDelete();
            $table->text('material')->nullable();
            $table->timestamps();

            $table->unique(['semester_id', 'subject_assignment_id']);
            $table->index(['institution_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_entry_contexts');
    }
};
