<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('username')
            ->orderBy('id')
            ->eachById(function (object $user): void {
                $candidate = Str::of((string) ($user->email ?? ''))
                    ->before('@')
                    ->lower()
                    ->replaceMatches('/[^a-z0-9]+/', '-')
                    ->trim('-')
                    ->value();

                $candidate = $candidate !== '' ? $candidate : 'user-'.$user->id;
                $baseCandidate = $candidate;
                $suffix = 1;

                while (DB::table('users')->where('username', $candidate)->exists()) {
                    $candidate = $baseCandidate.'-'.$suffix;
                    $suffix++;
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $candidate]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Username backfills are intentionally not reversed because values may be edited later.
    }
};
