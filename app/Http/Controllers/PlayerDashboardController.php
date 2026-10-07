<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventRegistration;
use App\Services\Matchmaker;
use Illuminate\Http\Request;

class PlayerDashboardController extends Controller
{
    public function __invoke(Request $request, Matchmaker $matchmaker)
    {
        $user = $request->user();
        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $registrations = EventRegistration::where('user_id', $user->id)->with('event')->get();
        $registrationIds = $registrations->pluck('id');

        // Every game this player has been drawn into, newest first, with both teams loaded.
        $games = EventMatch::with(['event', 'players'])
            ->whereHas('players', fn ($q) => $q
                ->whereIn('event_registrations.id', $registrationIds))
            ->latest('id')
            ->get()
            ->map(function (EventMatch $match) use ($registrationIds) {
                $me = $match->players->first(fn ($player) => $registrationIds->contains($player->id));
                $myTeam = (int) $me->pivot->team;
                $match->setAttribute('my_team', $myTeam);
                // Game 1, 2, 3… within its event (other players' names are not shown to players).
                $match->setAttribute('game_number', EventMatch::where('event_id', $match->event_id)
                    ->where('id', '<=', $match->id)
                    ->count());

                $mine = $myTeam === 1 ? $match->score_a : $match->score_b;
                $theirs = $myTeam === 1 ? $match->score_b : $match->score_a;
                $match->setAttribute('my_score', $mine);
                $match->setAttribute('their_score', $theirs);
                $match->setAttribute('won', $match->isComplete() ? $mine > $theirs : null);

                return $match;
            });

        $finished = $games->filter(fn ($game) => $game->isComplete());
        $currentGame = $games->first(fn ($game) => ! $game->isComplete() && ! $game->event->isPast());

        $myEvents = $registrations->pluck('event')
            ->filter(fn ($event) => ! $event->isPast())
            ->sortBy(fn ($event) => $event->date->format('Y-m-d').' '.$event->time)
            ->values();
        $myEventIds = $myEvents->pluck('id');
        $eventStatus = $myEvents->mapWithKeys(function (Event $event) use ($matchmaker, $registrations) {
            $registration = $registrations->firstWhere('event_id', $event->id);
            $stat = $matchmaker->stats($event)[$registration->id] ?? null;

            return [$event->id => (object) [
                'player_name' => $registration->player_name,
                'games' => $stat?->games ?? 0,
                'active' => $stat?->active ?? false,
            ]];
        });

        $openEvents = Event::upcoming()
            ->withCount('playerRegistrations')
            ->whereNotIn('id', $myEventIds)
            ->orderBy('date')
            ->orderBy('time')
            ->take(6)
            ->get();

        $bookings = Booking::where('guest_email', $user->email)
            ->where('status', 'confirmed')
            ->whereDate('booking_date', '>=', today())
            ->orderBy('booking_date')
            ->orderBy('hour')
            ->get()
            ->groupBy(fn ($booking) => $booking->booking_date->format('Y-m-d'));

        $wins = $finished->where('won', true)->count();
        $stats = [
            'events' => $registrations->count(),
            'games' => $finished->count(),
            'wins' => $wins,
            'win_rate' => $finished->count() ? round($wins / $finished->count() * 100) : null,
        ];

        return view('player.dashboard', [
            'nextEvent' => $myEvents->first(),
            'myEvents' => $myEvents,
            'eventStatus' => $eventStatus,
            'openEvents' => $openEvents,
            'currentGame' => $currentGame,
            'recentGames' => $finished->take(5),
            'bookings' => $bookings,
            'stats' => $stats,
        ]);
    }
}
