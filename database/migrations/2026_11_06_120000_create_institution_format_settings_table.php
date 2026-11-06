<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_format_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('student_name_format', 255)->default('{full_name}');
            $table->string('document_number_format', 255)->default('{document_number}');
            $table->string('date_format', 32)->default('d/m/Y');
            $table->string('signature_left_role', 20)->default('GURU');
            $table->string('signature_left_label', 100)->default('Wali Kelas');
            $table->string('signature_right_role', 20)->default('KEPSEK');
            $table->string('signature_right_label', 100)->default('Kepala PKBM');
            $table->boolean('show_logo')->default(true);
            $table->string('logo_position', 10)->default('center');
            $table->unsignedTinyInteger('logo_width_mm')->default(25);
            $table->unsignedSmallInteger('margin_top_mm')->default(15);
            $table->unsignedSmallInteger('margin_right_mm')->default(15);
            $table->unsignedSmallInteger('margin_bottom_mm')->default(15);
            $table->unsignedSmallInteger('margin_left_mm')->default(15);
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->boolean('show_kkm')->default(true);
            $table->boolean('show_predicate')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_format_settings');
    }
};
