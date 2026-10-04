<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('player_name', 100);
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });
        Schema::create('event_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score_a')->nullable();
            $table->unsignedInteger('score_b')->nullable();
            $table->timestamps();
        });
        Schema::create('event_match_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('event_matches')->cascadeOnDelete();
            $table->foreignId('event_registration_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('team');
            $table->unique(['match_id', 'event_registration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_match_players');
        Schema::dropIfExists('event_matches');
        Schema::dropIfExists('event_registrations');
    }
};
