<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->foreignId('reporting_enrollment_id')
                ->nullable()
                ->after('enrollment_status')
                ->constrained('enrollments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropForeign(['reporting_enrollment_id']);
            $table->dropColumn('reporting_enrollment_id');
        });
    }
};
