<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_grades', function (Blueprint $table): void {
            $table->string('predicate_snapshot')->nullable()->after('minimum_score_snapshot');
            $table->json('predicate_ranges_snapshot')->nullable()->after('predicate_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('report_grades', function (Blueprint $table): void {
            $table->dropColumn(['predicate_snapshot', 'predicate_ranges_snapshot']);
        });
    }
};
