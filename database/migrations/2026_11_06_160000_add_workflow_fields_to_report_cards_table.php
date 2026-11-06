<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_cards', function (Blueprint $table): void {
            $table->timestamp('submitted_at')->nullable()->after('published_at');
            $table->foreignId('submitted_by_user_id')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('submitted_by_user_id');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('reviewed_by_user_id');
            $table->foreignId('rejected_by_user_id')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('rejected_by_user_id');
            $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            $table->foreignId('approved_by_user_id')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->foreignId('locked_by_user_id')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
            $table->foreignId('unlocked_by_user_id')->nullable()->after('unlocked_at')->constrained('users')->nullOnDelete();
            $table->text('unlock_reason')->nullable()->after('unlocked_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('report_cards', function (Blueprint $table): void {
            $table->dropForeign(['submitted_by_user_id']);
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropForeign(['rejected_by_user_id']);
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['locked_by_user_id']);
            $table->dropForeign(['unlocked_by_user_id']);
            $table->dropColumn([
                'submitted_at',
                'submitted_by_user_id',
                'reviewed_at',
                'reviewed_by_user_id',
                'rejected_at',
                'rejected_by_user_id',
                'rejection_reason',
                'approved_at',
                'approved_by_user_id',
                'locked_by_user_id',
                'unlocked_by_user_id',
                'unlock_reason',
            ]);
        });
    }
};
