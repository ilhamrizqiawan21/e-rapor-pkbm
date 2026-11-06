<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->string('npsn', 20)->nullable()->after('email');
            $table->string('permit_number', 100)->nullable()->after('npsn');
            $table->string('postal_code', 10)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn(['npsn', 'permit_number', 'postal_code']);
        });
    }
};
