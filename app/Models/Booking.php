<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'guest_name',
        'guest_email',
        'booking_date',
        'court',
        'hour',
        'status',
    ];

    // Serialise as a plain date so the booking calendar can match it exactly.
    protected $casts = [
        'booking_date' => 'date:Y-m-d',
    ];
}
