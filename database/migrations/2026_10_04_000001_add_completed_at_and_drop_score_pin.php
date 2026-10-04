<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Scores now autosave while a game is in progress, so "has a score" no longer
 * means "finished". completed_at marks a game that reached a winning score.
 * The scorekeeper PIN is no longer used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_matches', function (Blueprint $table): void {
            $table->timestamp('completed_at')->nullable()->after('score_b');
        });

        // Every game saved under the old flow had a valid final score.
        DB::table('event_matches')
            ->whereNotNull('score_a')
            ->whereNotNull('score_b')
            ->update(['completed_at' => DB::raw('updated_at')]);

        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('score_pin');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('score_pin')->nullable()->after('capacity');
        });

        Schema::table('event_matches', function (Blueprint $table): void {
            $table->dropColumn('completed_at');
        });
    }
};
