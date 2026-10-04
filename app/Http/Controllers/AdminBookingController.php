<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBookingController extends Controller
{
    public function index()
    {
        return view('admin.bookings.index', [
            'bookings' => Booking::latest()->get(),
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'guest_name' => 'required|string|max:120',
            'guest_email' => 'required|email',
            'booking_date' => 'required|date',
            'court' => ['required', Rule::in(['PickleHub Court'])],
            'hour' => 'required|integer|between:7,23',
            'status' => 'required|in:confirmed,cancelled',
        ]);

        $conflict = Booking::where('id', '!=', $booking->id)
            ->whereDate('booking_date', $data['booking_date'])
            ->where('court', $data['court'])
            ->where('hour', $data['hour'])
            ->where('status', 'confirmed')
            ->exists();

        if ($data['status'] === 'confirmed' && $conflict) {
            return back()
                ->withErrors(['booking_date' => 'That court slot is already booked.'])
                ->withInput();
        }

        try {
            $booking->update($data);
        } catch (QueryException $e) {
            // The slot is held by another (cancelled) row in the unique index.
            return back()
                ->withErrors(['booking_date' => 'That court slot is already taken by another booking.'])
                ->withInput();
        }

        return back()->with('message', 'Booking updated.');
    }

    public function destroy(Booking $booking)
    {
        $booking->update(['status' => 'cancelled']);

        return back();
    }
}
