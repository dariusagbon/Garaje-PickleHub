<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /** Confirmed slots, used by the calendar to grey out booked hours. */
    public function availability(Request $request)
    {
        $bookings = Booking::where('status', 'confirmed')
            ->get(['booking_date', 'court', 'hour']);

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        // Older clients send a single "hour"; treat it as a one-item "hours" list.
        if (! $request->has('hours') && $request->has('hour')) {
            $request->merge(['hours' => [$request->input('hour')]]);
        }

        $data = $request->validate([
            'guest_name' => 'required|string|max:120',
            'guest_email' => 'required|email|max:255',
            'booking_date' => 'required|date|after_or_equal:today',
            'court' => ['required', Rule::in(['PickleHub Court'])],
            'hours' => 'required|array|min:1',
            'hours.*' => 'required|integer|between:7,23',
        ]);
        $data['hours'] = array_values(array_unique($data['hours']));

        $conflict = Booking::whereDate('booking_date', $data['booking_date'])
            ->where('court', $data['court'])
            ->whereIn('hour', $data['hours'])
            ->where('status', 'confirmed')
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'One or more selected hours are already booked.'], 409);
        }

        try {
            $bookings = DB::transaction(fn () => collect($data['hours'])
                ->map(fn ($hour) => $this->bookHour($data, $hour)));
        } catch (QueryException $e) {
            // The unique index caught a booking made at the same moment.
            return response()->json(['message' => 'That slot is already booked.'], 409);
        }

        return response()->json([
            'message' => count($data['hours']).' booking hours confirmed.',
            'bookings' => $bookings,
        ], 201);
    }

    /** Create the booking, or re-use a cancelled row for the same slot. */
    private function bookHour(array $data, int $hour): Booking
    {
        $attributes = [
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'],
            'booking_date' => $data['booking_date'],
            'court' => $data['court'],
            'hour' => $hour,
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
