<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Court rates (Philippine pesos per hour)
    |--------------------------------------------------------------------------
    |
    | Hours that start before "evening_starts_at" use the day rate; hours from
    | then on use the evening rate. 8 AM–5 PM is ₱200, 6 PM onwards is ₱250.
    | The 7 AM slot (before the stated day hours) is charged the day rate.
    |
    */

    'day_rate' => (int) env('BOOKING_DAY_RATE', 200),

    'evening_rate' => (int) env('BOOKING_EVENING_RATE', 250),

    'evening_starts_at' => 18,

    'currency_symbol' => '₱',

    /*
    |--------------------------------------------------------------------------
    | Email verification
    |--------------------------------------------------------------------------
    |
    | Before a booking is accepted, a 6-digit code is emailed to the address
    | given. "check_dns" also rejects addresses whose domain cannot receive mail.
    |
    */

    'code_minutes' => 10,

    'code_attempts' => 5,

    'resend_seconds' => 60,

    // Once an address is verified, it stays verified in that browser for this long.
    'verified_for_hours' => 3,

    'check_dns' => (bool) env('BOOKING_EMAIL_DNS_CHECK', true),

];
