<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    protected $fillable = [
        'reference',
        'guest_name',
        'guest_email',
        'booking_date',
        'court',
        'hour',
        'price',
        'status',
    ];

    // Serialise as a plain date so the booking calendar can match it exactly.
    protected $casts = [
        'booking_date' => 'date:Y-m-d',
        'price' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    */

    /** Price in pesos for an hour starting at $hour (day or evening rate). */
    public static function rateFor(int $hour): int
    {
        return $hour >= config('booking.evening_starts_at')
            ? config('booking.evening_rate')
            : config('booking.day_rate');
    }

    public static function rateLabelFor(int $hour): string
    {
        return $hour >= config('booking.evening_starts_at') ? 'Evening rate' : 'Day rate';
    }

    public static function peso(int $amount): string
    {
        return config('booking.currency_symbol').number_format($amount);
    }

    /** Price for this booking, falling back to today's rate for older rows. */
    public function amount(): int
    {
        return $this->price ?? self::rateFor($this->hour);
    }

    /** "6:00 PM – 7:00 PM" */
    public function timeRange(): string
    {
        $start = Carbon::createFromTime($this->hour);

        return $start->format('g:i A').' – '.$start->copy()->addHour()->format('g:i A');
    }

    /*
    |--------------------------------------------------------------------------
    | Receipt reference
    |--------------------------------------------------------------------------
    */

    /** A short, unique, easy-to-read code such as GPH-261009-K7Q2M. */
    public static function newReference(): string
    {
        // No 0/O or 1/I, so the code is easy to read out at the front desk.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = collect(range(1, 5))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
            $reference = 'GPH-'.now()->format('ymd').'-'.$suffix;
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }
}
