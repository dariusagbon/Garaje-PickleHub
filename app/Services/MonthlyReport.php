<?php

namespace App\Services;

use App\Http\Controllers\AdminDashboardController;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Figures for one calendar month of court bookings and events,
 * used by the printable admin report.
 */
class MonthlyReport
{
    public readonly Carbon $start;

    public readonly Carbon $end;

    /** @var int[] Bookable hours each day (7 AM to 11 PM starts). */
    public readonly array $hours;

    private ?Collection $bookings = null;

    public function __construct(Carbon $month)
    {
        $this->start = $month->copy()->startOfMonth();
        $this->end = $month->copy()->endOfMonth()->startOfDay();
        $this->hours = range(...AdminDashboardController::OPEN_HOURS);
    }

    /*
    |--------------------------------------------------------------------------
    | Bookings
    |--------------------------------------------------------------------------
    */

    /** Every booking in the month, confirmed and cancelled, in calendar order. */
    public function bookings(): Collection
    {
        return $this->bookings ??= Booking::whereDate('booking_date', '>=', $this->start)
            ->whereDate('booking_date', '<=', $this->end)
            ->orderBy('booking_date')
            ->orderBy('hour')
            ->get();
    }

    public function bookingSummary(): array
    {
        $bookings = $this->bookings();
        $confirmed = $bookings->where('status', 'confirmed');
        $capacity = count($this->hours) * $this->start->daysInMonth;

        $busiestDay = $confirmed
            ->countBy(fn ($booking) => $booking->booking_date->format('Y-m-d'))
            ->sortDesc()
            ->keys()
            ->first();

        $busiestHour = $confirmed
            ->countBy('hour')
            ->sortDesc()
            ->keys()
            ->first();

        return [
            'confirmed_hours' => $confirmed->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
            'guests' => $confirmed->pluck('guest_email')->map(fn ($email) => strtolower($email))->unique()->count(),
            'capacity_hours' => $capacity,
            'utilisation' => $capacity ? round($confirmed->count() / $capacity * 100, 1) : 0,
            'busiest_day' => $busiestDay ? Carbon::parse($busiestDay) : null,
            'busiest_hour' => $busiestHour,
        ];
    }

    /** Booked hours for every day of the month, including empty days. */
    public function bookingsPerDay(): Collection
    {
        $perDay = $this->bookings()
            ->where('status', 'confirmed')
            ->countBy(fn ($booking) => $booking->booking_date->format('Y-m-d'));

        return collect(range(0, $this->start->daysInMonth - 1))->map(function ($offset) use ($perDay) {
            $day = $this->start->copy()->addDays($offset);
            $booked = $perDay[$day->format('Y-m-d')] ?? 0;

            return [
                'date' => $day,
                'hours' => $booked,
                'utilisation' => round($booked / count($this->hours) * 100),
            ];
        });
    }

    /** How many times each start hour was booked across the month. */
    public function bookingsPerHour(): Collection
    {
        $perHour = $this->bookings()
            ->where('status', 'confirmed')
            ->countBy('hour');

        return collect($this->hours)->mapWithKeys(fn ($hour) => [$hour => $perHour[$hour] ?? 0]);
    }

    /** Guests ranked by confirmed hours booked. */
    public function topGuests(int $limit = 10): Collection
    {
        return $this->bookings()
            ->where('status', 'confirmed')
            ->groupBy(fn ($booking) => strtolower($booking->guest_email))
            ->map(fn ($bookings) => [
                'name' => $bookings->last()->guest_name,
                'email' => $bookings->first()->guest_email,
                'hours' => $bookings->count(),
                'days' => $bookings->unique(fn ($booking) => $booking->booking_date->format('Y-m-d'))->count(),
            ])
            ->sortByDesc('hours')
            ->take($limit)
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    public function events(): Collection
    {
        return Event::whereDate('date', '>=', $this->start)
            ->whereDate('date', '<=', $this->end)
            ->with(['playerRegistrations.user'])
            ->withCount([
                'playerRegistrations',
                'matches',
                'matches as completed_matches_count' => fn ($q) => $q
                    ->whereNotNull('score_a')
                    ->whereNotNull('score_b'),
            ])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    public function eventSummary(): array
    {
        $events = $this->events();
        $capacity = $events->sum('capacity');
        $registrations = $events->sum('player_registrations_count');

        return [
            'events' => $events->count(),
            'registrations' => $registrations,
            'players' => $events->flatMap->playerRegistrations->pluck('user_id')->unique()->count(),
            'games' => $events->sum('completed_matches_count'),
            'fill_rate' => $capacity ? (int) round($registrations / $capacity * 100) : 0,
            'top_event' => $events->sortByDesc('player_registrations_count')->first(),
        ];
    }
}
