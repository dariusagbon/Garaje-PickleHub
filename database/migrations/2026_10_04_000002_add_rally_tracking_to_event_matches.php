<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Event games are now scored rally by rally with side-out rules
 * (App\Services\SideOutScoring). The score is derived from:
 *  - rallies:            winner of each rally in order, e.g. ["A","B","B"]
 *  - first_serving_team: which team served first ("A" or "B")
 *  - start_score_a/b:    score already on the board when rally tracking began
 *                        (only for games scored before this change; otherwise 0)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_matches', function (Blueprint $table): void {
            $table->json('rallies')->nullable()->after('score_b');
            $table->char('first_serving_team', 1)->default('A')->after('rallies');
            $table->unsignedInteger('start_score_a')->default(0)->after('first_serving_team');
            $table->unsignedInteger('start_score_b')->default(0)->after('start_score_a');
        });
    }

    public function down(): void
    {
        Schema::table('event_matches', function (Blueprint $table): void {
            $table->dropColumn(['rallies', 'first_serving_team', 'start_score_a', 'start_score_b']);
        });
    }
};
