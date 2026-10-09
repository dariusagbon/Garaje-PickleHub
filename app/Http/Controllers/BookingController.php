<?php

namespace App\Http\Controllers;

use App\Mail\BookingReceipt;
use App\Models\Booking;
use App\Services\BookingEmailVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Throwable;

class BookingController extends Controller
{
    public function __construct(private BookingEmailVerifier $verifier) {}

    /** Confirmed slots, used by the calendar to grey out booked hours. */
    public function availability(Request $request)
    {
        $bookings = Booking::where('status', 'confirmed')
            ->get(['booking_date', 'court', 'hour']);

        return response()->json($bookings);
    }

    /*
    |--------------------------------------------------------------------------
    | Step 1: verify the email address
    |--------------------------------------------------------------------------
    */

    /** Email a 6-digit code (or say the address is already verified in this browser). */
    public function verifyEmail(Request $request)
    {
        $data = $request->validate([
            'guest_name' => 'required|string|max:120',
            'guest_email' => $this->emailRules(),
        ]);

        if ($this->verifier->isVerified($data['guest_email'])) {
            return response()->json(['verified' => true]);
        }

        try {
            $wait = $this->verifier->sendCode($data['guest_email'], $data['guest_name']);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'We could not send a code to that address. Please check it and try again.',
            ], 503);
        }

        if ($wait) {
            return response()->json([
                'message' => "A code was just sent. You can ask for a new one in {$wait} seconds.",
                'code_sent' => true,
                'retry_after' => $wait,
            ], 429);
        }

        return response()->json([
            'code_sent' => true,
            'message' => "We sent a 6-digit code to {$data['guest_email']}. It expires in ".config('booking.code_minutes').' minutes.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Step 2: book the hours
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        // Older clients send a single "hour"; treat it as a one-item "hours" list.
        if (! $request->has('hours') && $request->has('hour')) {
            $request->merge(['hours' => [$request->input('hour')]]);
        }

        $data = $request->validate([
            'guest_name' => 'required|string|max:120',
            'guest_email' => $this->emailRules(),
            'booking_date' => 'required|date|after_or_equal:today',
            'court' => ['required', Rule::in(['PickleHub Court'])],
            'hours' => 'required|array|min:1',
            'hours.*' => 'required|integer|between:7,23',
            'verification_code' => 'nullable|string|max:12',
        ]);
        $data['hours'] = collect($data['hours'])->unique()->sort()->values()->all();

        if (! $this->verifier->isVerified($data['guest_email'])
            && ! $this->verifier->check($data['guest_email'], $data['verification_code'] ?? null)) {
            return response()->json([
                'message' => 'Please enter the 6-digit code we emailed you.',
                'errors' => ['verification_code' => ['That code is incorrect or has expired. Check your email or request a new code.']],
            ], 422);
        }

        $conflict = Booking::whereDate('booking_date', $data['booking_date'])
            ->where('court', $data['court'])
            ->whereIn('hour', $data['hours'])
            ->where('status', 'confirmed')
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'One or more selected hours are already booked.'], 409);
        }

        $reference = Booking::newReference();

        try {
            $bookings = DB::transaction(fn () => collect($data['hours'])
                ->map(fn ($hour) => $this->bookHour($data, $hour, $reference)));
        } catch (QueryException $e) {
            // The unique index caught a booking made at the same moment.
            return response()->json(['message' => 'That slot is already booked.'], 409);
        }

        $receiptUrl = $this->receiptUrl($reference);
        $emailed = $this->sendReceipt($bookings, $receiptUrl);
        $total = $bookings->sum(fn ($booking) => $booking->amount());
        $count = $bookings->count();

        return response()->json([
            'message' => ($count === 1 ? '1 court hour' : "{$count} court hours").' confirmed.',
            'reference' => $reference,
            'total' => $total,
            'total_label' => Booking::peso($total),
            'receipt_url' => $receiptUrl,
            'receipt_emailed' => $emailed,
            'bookings' => $bookings,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Receipt
    |--------------------------------------------------------------------------
    */

    /** Printable receipt; the link is signed, so only the person who booked has it. */
    public function receipt(string $reference)
    {
        $bookings = Booking::where('reference', $reference)->orderBy('hour')->get();
        abort_if($bookings->isEmpty(), 404);

        $confirmed = $bookings->where('status', 'confirmed');

        return view('bookings.receipt', [
            'booking' => $bookings->first(),
            'bookings' => $bookings,
            'total' => $confirmed->sum(fn ($booking) => $booking->amount()),
            'cancelled' => $confirmed->isEmpty(),
        ]);
    }

    private function receiptUrl(string $reference): string
    {
        return URL::signedRoute('bookings.receipt', ['reference' => $reference]);
    }

    /** Email the receipt; a mail problem must not undo a confirmed booking. */
    private function sendReceipt(Collection $bookings, string $receiptUrl): bool
    {
        try {
            Mail::to($bookings->first()->guest_email)->send(new BookingReceipt($bookings, $receiptUrl));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /** A well-formed address whose domain can receive mail (DNS check can be turned off). */
    private function emailRules(): array
    {
        return ['required', 'max:255', config('booking.check_dns') ? 'email:rfc,dns' : 'email:rfc'];
    }

    /** Create the booking, or re-use a cancelled row for the same slot. */
    private function bookHour(array $data, int $hour, string $reference): Booking
    {
        $attributes = [
            'reference' => $reference,
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'],
            'booking_date' => $data['booking_date'],
            'court' => $data['court'],
            'hour' => $hour,
            'price' => Booking::rateFor($hour),
            'status' => 'confirmed',
        ];

        $existing = Booking::whereDate('booking_date', $data['booking_date'])
            ->where('court', $data['court'])
            ->where('hour', $hour)
            ->first();

        if ($existing) {
            $existing->update($attributes);

            return $existing->fresh();
        }

        return Booking::create($attributes);
    }
}
