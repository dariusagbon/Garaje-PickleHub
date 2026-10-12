<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One account can register several players (e.g. friends or family) for the same event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            // Keep an index for the event_id foreign key before the unique one goes.
            $table->index(['event_id', 'user_id']);
            $table->dropUnique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->unique(['event_id', 'user_id']);
            $table->dropIndex(['event_id', 'user_id']);
        });
    }
};
