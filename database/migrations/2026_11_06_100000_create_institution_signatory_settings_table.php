<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_signatory_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headmaster_source')->default('ACCOUNT');
            $table->foreignId('headmaster_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('headmaster_name')->nullable();
            $table->string('headmaster_title')->nullable();
            $table->string('headmaster_employee_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_signatory_settings');
    }
};
