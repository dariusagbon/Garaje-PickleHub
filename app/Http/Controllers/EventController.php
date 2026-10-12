<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventRegistration;
use App\Services\Matchmaker;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Public event pages
    |--------------------------------------------------------------------------
    */

    public function show(Event $event, Matchmaker $matchmaker)
    {
        $event->load(['playerRegistrations', 'matches.players']);
        $stats = $matchmaker->stats($event);

        return view('events.show', compact('event', 'stats'));
    }

    /** Adds a player to the event. One account can register several players (friends, family). */
    public function register(Request $request, Event $event, Matchmaker $matchmaker)
    {
        if ($event->isPast()) {
            return back()->withErrors(['event' => 'This event has already ended.']);
        }

        $data = $request->validate([
            'player_name' => 'required|string|max:100',
        ]);
        $name = trim($data['player_name']);
        $userId = $request->user()->id;

        $alreadyAdded = $event->playerRegistrations()
            ->where('user_id', $userId)
            ->where('player_name', $name)
            ->exists();
        if ($alreadyAdded) {
            return back()->withErrors(['player_name' => "You already registered $name for this event."]);
        }

        if ($event->playerRegistrations()->count() >= $event->capacity) {
            return back()->withErrors(['event' => 'This event is full.']);
        }

        $event->playerRegistrations()->create(['user_id' => $userId, 'player_name' => $name]);
        $event->users()->syncWithoutDetaching([$userId]);

        $created = $matchmaker->fill($event);

        return back()->with('message', $created
            ? "$name is registered and a new game is ready!"
            : "$name is registered and will be placed in the next game.");
    }

    /** Renames one of the account's players. */
    public function renamePlayer(Request $request, Event $event, EventRegistration $registration)
    {
        $this->authorizeRegistration($request, $event, $registration);

        if ($event->isPast()) {
            return back()->withErrors(['event' => 'This event has already ended.']);
        }

        $data = $request->validate([
            'player_name' => 'required|string|max:100',
        ]);
        $registration->update(['player_name' => trim($data['player_name'])]);

        return back()->with('message', 'Player name updated.');
    }

    /** Removes one of the account's players, as long as they have not been drawn into a game. */
    public function removePlayer(Request $request, Event $event, EventRegistration $registration)
    {
        $this->authorizeRegistration($request, $event, $registration);

        if ($event->isPast()) {
            return back()->withErrors(['event' => 'This event has already ended.']);
        }

        if ($registration->matches()->exists()) {
            return back()->withErrors(['event' => "{$registration->player_name} has already been drawn into a game and can't be removed."]);
        }

        $registration->delete();

        $userId = $request->user()->id;
        if (! $event->playerRegistrations()->where('user_id', $userId)->exists()) {
            $event->users()->detach($userId);
        }

        return back()->with('message', "{$registration->player_name} was removed from the event.");
    }

    private function authorizeRegistration(Request $request, Event $event, EventRegistration $registration): void
    {
        abort_unless($registration->event_id === $event->id, 404);
        abort_unless($registration->user_id === $request->user()->id, 403);
    }

    /**
     * Records one scoring action on a game, using side-out rules.
     * Sent in the background as each rally is tapped, or as a normal form
     * post when JavaScript is off.
     *
     * Actions: rally_a / rally_b (who won the rally), undo, serve_a / serve_b
     * (which team serves first, before the first rally).
     */
    public function score(Request $request, Event $event, EventMatch $match, Matchmaker $matchmaker)
    {
        abort_unless($match->event_id === $event->id, 404);

        $data = $request->validate([
            'action' => 'required|in:rally_a,rally_b,undo,serve_a,serve_b',
            // How many rallies the scorer's screen showed; guards against two devices scoring at once.
            'rallies_seen' => 'nullable|integer|min:0',
        ]);

        $recorded = count($match->rallies ?? []);
        if (isset($data['rallies_seen']) && (int) $data['rallies_seen'] !== $recorded) {
            return $this->scoreResponse($request, $match, false, 409,
                'This game was updated on another device. The latest score is now shown.');
        }

        $wasComplete = $match->isComplete();

        $applied = match ($data['action']) {
            'rally_a' => $match->recordRally('A'),
            'rally_b' => $match->recordRally('B'),
            'undo' => $match->undoRally(),
            'serve_a' => $match->setFirstServingTeam('A'),
            'serve_b' => $match->setFirstServingTeam('B'),
        };

        if (! $applied) {
            $message = match ($data['action']) {
                'undo' => 'There is nothing to undo.',
                'serve_a', 'serve_b' => 'The first server can only be changed before the first rally.',
                default => 'This game is over. Undo the last rally to correct it.',
            };

            return $this->scoreResponse($request, $match, false, 422, $message);
        }

        // The game just ended: free its players and draw the next game.
        $nextGameReady = ! $wasComplete && $match->isComplete() && $matchmaker->fill($event) > 0;

        return $this->scoreResponse($request, $match, $nextGameReady);
    }

    private function scoreResponse(Request $request, EventMatch $match, bool $nextGameReady, int $status = 200, ?string $error = null)
    {
        $state = $match->scoring();

        if ($request->expectsJson()) {
            return response()->json([
                ...$state,
                'winner_label' => $match->winnerLabel(),
                'can_undo' => ! empty($match->rallies),
                'next_game_ready' => $nextGameReady,
                'message' => $error,
            ], $status);
        }

        if ($error) {
            return back()->withErrors(['score' => $error]);
        }

        return back()->with('message', match (true) {
            $nextGameReady => 'Game over! The next game is ready.',
            $state['complete'] => 'Game over! '.$match->winnerLabel().'.',
            default => 'Score '.$state['call'].'.',
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $events = Event::upcoming()
            ->with('playerRegistrations')
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return view('admin.events.index', [
            'events' => $events,
            'pastCount' => Event::past()->count(),
        ]);
    }

    public function history()
    {
        $events = Event::past()
            ->with(['playerRegistrations.user', 'matches'])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();

        return view('admin.events.history', ['events' => $events]);
    }

    public function create()
    {
        return view('admin.events.form', ['event' => new Event]);
    }

    public function store(Request $request)
    {
        Event::create($request->validate($this->rules()));

        return redirect()->route('admin.events.index');
    }

    public function edit(Event $event)
    {
        return view('admin.events.form', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $event->update($request->validate($this->rules()));

        return redirect()->route('admin.events.index');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return back();
    }

    /** Reshuffle games that have not started yet (admin only). */
    public function randomize(Event $event, Matchmaker $matchmaker)
    {
        if ($event->isPast()) {
            return back()->withErrors(['event' => 'This event has already ended.']);
        }

        $matchmaker->reshuffle($event);

        return back()->with('message', 'Games that had not started were reshuffled. Games in progress and finished games were kept.');
    }

    private function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'date' => 'required|date',
            'time' => 'required',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
        ];
    }
}
