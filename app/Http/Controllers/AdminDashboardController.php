<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public const OPEN_HOURS = [7, 23];

    public function __invoke()
    {
        $hours = range(self::OPEN_HOURS[0], self::OPEN_HOURS[1]);
        $today = today();

        $todayBookings = Booking::where('status', 'confirmed')->whereDate('booking_date', $today)->get()->keyBy('hour');

        // Booked hours per day for the next 14 days, including days with no bookings.
        $horizon = collect(range(0, 13))->map(fn ($offset) => $today->copy()->addDays($offset));
        $perDay = Booking::where('status', 'confirmed')
            ->whereDate('booking_date', '>=', $today)
            ->whereDate('booking_date', '<=', $horizon->last())
            ->get()
            ->countBy(fn ($booking) => $booking->booking_date->format('Y-m-d'));
        $forecast = $horizon->map(fn (Carbon $day) => [
            'date' => $day,
            'hours' => $perDay[$day->format('Y-m-d')] ?? 0,
        ]);

        $upcomingEvents = Event::upcoming()
            ->withCount([
                'playerRegistrations',
                'matches',
                'matches as live_matches_count' => fn ($q) => $q->unfinished(),
            ])
            ->orderBy('date')
            ->orderBy('time')
            ->take(5)
            ->get();

        $activity = Booking::latest()->take(8)->get()->map(fn ($booking) => (object) [
            'type' => $booking->status === 'cancelled' ? 'cancelled' : 'booking',
            'at' => $booking->updated_at,
            'title' => $booking->guest_name,
            'detail' => ($booking->status === 'cancelled' ? 'Cancelled ' : 'Booked ')
                .$booking->booking_date->format('M j')
                .' · '.Carbon::createFromTime($booking->hour)->format('g:i A'),
            'url' => route('admin.bookings.index'),
        ])->concat(EventRegistration::with('event')->latest()->take(8)->get()->map(fn ($registration) => (object) [
            'type' => 'registration',
            'at' => $registration->created_at,
            'title' => $registration->player_name,
            'detail' => 'Joined '.$registration->event->title,
            'url' => route('events.show', $registration->event),
        ]))->sortByDesc('at')->take(8)->values();

        $weekHours = $forecast->take(7)->sum('hours');

        return view('admin.dashboard', [
            'hours' => $hours,
            'todayBookings' => $todayBookings,
            'forecast' => $forecast,
            'upcomingEvents' => $upcomingEvents,
            'activity' => $activity,
            'stats' => [
                'today_hours' => $todayBookings->count(),
                'today_rate' => round($todayBookings->count() / count($hours) * 100),
                'week_hours' => $weekHours,
                'week_rate' => round($weekHours / (count($hours) * 7) * 100),
                'upcoming_events' => Event::upcoming()->count(),
                'players' => EventRegistration::whereHas('event', fn ($q) => $q->upcoming())->count(),
                'live_games' => EventMatch::unfinished()->whereHas('event', fn ($q) => $q->upcoming())->count(),
                'members' => User::where('is_admin', false)->count(),
            ],
        ]);
    }
}
