<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_cards', function (Blueprint $table): void {
            $table->json('identity_snapshot')->nullable()->after('document_number');
        });
    }

    public function down(): void
    {
        Schema::table('report_cards', function (Blueprint $table): void {
            $table->dropColumn('identity_snapshot');
        });
    }
};
