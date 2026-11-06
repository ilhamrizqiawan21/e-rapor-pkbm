<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->unsignedSmallInteger('display_order')->default(1)->after('category');
            $table->index(['institution_id', 'active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropIndex('subjects_institution_id_active_display_order_index');
            $table->dropColumn('display_order');
        });
    }
};
