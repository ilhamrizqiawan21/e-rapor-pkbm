<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_signatures', function (Blueprint $table): void {
            $table->string('signer_employee_number_snapshot')->nullable()->after('signer_title_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('report_signatures', function (Blueprint $table): void {
            $table->dropColumn('signer_employee_number_snapshot');
        });
    }
};
