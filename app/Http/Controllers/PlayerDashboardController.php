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
            ->whereHas('players', fn ($q) => $q->whereIn('event_registrations.id', $registrationIds))
            ->latest('id')
            ->get()
            ->map(function (EventMatch $match) use ($registrationIds) {
                $me = $match->players->first(fn ($player) => $registrationIds->contains($player->id));
                $myTeam = (int) $me->pivot->team;
                $match->setAttribute('my_team', $myTeam);
                $match->setAttribute('partners', $match->players->filter(fn ($p) => $p->pivot->team == $myTeam && $p->id !== $me->id)->pluck('player_name'));
                $match->setAttribute('opponents', $match->players->filter(fn ($p) => $p->pivot->team != $myTeam)->pluck('player_name'));
                $mine = $myTeam === 1 ? $match->score_a : $match->score_b;
                $theirs = $myTeam === 1 ? $match->score_b : $match->score_a;
                $match->setAttribute('my_score', $mine);
                $match->setAttribute('their_score', $theirs);
                $match->setAttribute('won', $match->isComplete() ? $mine > $theirs : null);
                return $match;
            });

        $finished = $games->filter(fn ($game) => $game->isComplete());
        $currentGame = $games->first(fn ($game) => ! $game->isComplete() && ! $game->event->isPast());

        $myEvents = $registrations->pluck('event')->filter(fn ($event) => ! $event->isPast())
            ->sortBy(fn ($event) => $event->date->format('Y-m-d').' '.$event->time)->values();
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

        $openEvents = Event::upcoming()->withCount('playerRegistrations')
            ->whereNotIn('id', $myEventIds)
            ->orderBy('date')->orderBy('time')->take(6)->get();

        $bookings = Booking::where('guest_email', $user->email)->where('status', 'confirmed')
            ->whereDate('booking_date', '>=', today())
            ->orderBy('booking_date')->orderBy('hour')->get()
            ->groupBy(fn ($booking) => $booking->booking_date->format('Y-m-d'));

        $stats = [
            'events' => $registrations->count(),
            'games' => $finished->count(),
            'wins' => $finished->where('won', true)->count(),
            'win_rate' => $finished->count() ? round($finished->where('won', true)->count() / $finished->count() * 100) : null,
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
