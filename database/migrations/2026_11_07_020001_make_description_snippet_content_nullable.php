<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('description_snippets', function (Blueprint $table): void {
            $table->text('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('description_snippets')->whereNull('content')->update(['content' => '']);

        Schema::table('description_snippets', function (Blueprint $table): void {
            $table->text('content')->nullable(false)->change();
        });
    }
};
