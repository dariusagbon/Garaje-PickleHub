<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Shared by every hour booked in one go; printed on the receipt.
            $table->string('reference', 20)->nullable()->index()->after('id');
            // Rate charged for the hour, in pesos, fixed at booking time.
            $table->unsignedSmallInteger('price')->nullable()->after('hour');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['reference']);
            $table->dropColumn(['reference', 'price']);
        });
    }
};
