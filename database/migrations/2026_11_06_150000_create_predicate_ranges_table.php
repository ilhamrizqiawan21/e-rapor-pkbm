<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predicate_ranges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('label', 100);
            $table->unsignedTinyInteger('minimum_score');
            $table->unsignedTinyInteger('maximum_score');
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['institution_id', 'code']);
            $table->index(['institution_id', 'active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predicate_ranges');
    }
};
