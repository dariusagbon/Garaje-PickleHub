<?php

namespace App\Services;

use App\Mail\BookingVerificationCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Proves a booking email address exists and belongs to the person booking:
 * a 6-digit code is emailed to it and must be entered before the booking
 * is accepted. Once verified, the address stays verified in that browser
 * session for a few hours so repeat bookings don't need a new code.
 */
class BookingEmailVerifier
{
    private const SESSION_KEY = 'booking.verified_emails';

    public function isVerified(string $email): bool
    {
        $verifiedAt = session(self::SESSION_KEY, [])[$this->normalise($email)] ?? null;

        return $verifiedAt !== null
            && $verifiedAt > now()->subHours(config('booking.verified_for_hours'))->timestamp;
    }

    /**
     * Email a fresh code. Returns the number of seconds to wait instead
     * if a code was sent to this address too recently.
     */
    public function sendCode(string $email, string $name): ?int
    {
        $entry = Cache::get($this->cacheKey($email));
        $wait = $entry ? $entry['sent_at'] + config('booking.resend_seconds') - now()->timestamp : 0;

        if ($wait > 0) {
            return $wait;
        }

        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(config('booking.code_minutes'));

        Cache::put($this->cacheKey($email), [
            'hash' => Hash::make($code),
            'attempts' => 0,
            'sent_at' => now()->timestamp,
            'expires_at' => $expiresAt->timestamp,
        ], $expiresAt);

        Mail::to($email)->send(new BookingVerificationCode($name, $code, config('booking.code_minutes')));

        return null;
    }

    /** Check a code; a correct one marks the address as verified. */
    public function check(string $email, ?string $code): bool
    {
        $key = $this->cacheKey($email);
        $entry = Cache::get($key);
        $code = preg_replace('/\D/', '', (string) $code);

        if (! $entry || $code === '') {
            return false;
        }

        if (! Hash::check($code, $entry['hash'])) {
            $entry['attempts']++;

            // Too many wrong guesses: the code is thrown away and a new one is needed.
            $entry['attempts'] >= config('booking.code_attempts')
                ? Cache::forget($key)
                : Cache::put($key, $entry, now()->setTimestamp($entry['expires_at']));

            return false;
        }

        Cache::forget($key);
        $this->markVerified($email);

        return true;
    }

    public function markVerified(string $email): void
    {
        session()->put(self::SESSION_KEY.'.'.$this->normalise($email), now()->timestamp);
    }

    private function cacheKey(string $email): string
    {
        return 'booking-code:'.sha1($this->normalise($email));
    }

    private function normalise(string $email): string
    {
        // Dots are not allowed in session keys used with put('a.b'), so hash the address.
        return sha1(strtolower(trim($email)));
    }
}
