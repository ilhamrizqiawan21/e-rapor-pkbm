<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('description_snippets', function (Blueprint $table): void {
            $table->text('material')->nullable();
        });

        Schema::create('description_snippet_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('description_snippet_id')->constrained()->cascadeOnDelete();
            $table->string('predicate_code', 32);
            $table->string('predicate_label', 100);
            $table->text('template');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['description_snippet_id', 'predicate_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('description_snippet_templates');
        Schema::table('description_snippets', function (Blueprint $table): void {
            $table->dropColumn('material');
        });
    }
};
