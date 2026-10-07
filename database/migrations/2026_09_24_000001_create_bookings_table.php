<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->date('booking_date');
            $table->string('court');
            $table->unsignedTinyInteger('hour');
            $table->string('status')->default('confirmed');
            $table->timestamps();
            $table->unique(['booking_date', 'court', 'hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
