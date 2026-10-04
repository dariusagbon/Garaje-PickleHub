<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventMatch;
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

    public function register(Request $request, Event $event, Matchmaker $matchmaker)
    {
        if ($event->isPast()) {
            return back()->withErrors(['event' => 'This event has already ended.']);
        }

        $data = $request->validate([
            'player_name' => 'required|string|max:100',
        ]);

        $userId = $request->user()->id;
        $registration = $event->playerRegistrations()
            ->where('user_id', $userId)
            ->first();

        if (! $registration && $event->playerRegistrations()->count() >= $event->capacity) {
            return back()->withErrors(['event' => 'This event is full.']);
        }

        $event->playerRegistrations()->updateOrCreate(
            ['user_id' => $userId],
            ['player_name' => $data['player_name']],
        );
        $event->users()->syncWithoutDetaching([$userId]);

        if ($registration) {
            return back()->with('message', 'Your player name was updated.');
        }

        $created = $matchmaker->fill($event);

        return back()->with('message', $created
            ? 'You are registered and a new game is ready!'
            : 'You are registered! You will be placed in the next game.');
    }

    /**
     * Saves a live score. Called in the background on every point (autosave),
     * or as a normal form post when JavaScript is off.
     */
    public function score(Request $request, Event $event, EventMatch $match, Matchmaker $matchmaker)
    {
        abort_unless($match->event_id === $event->id, 404);

        $data = $request->validate([
            'score_a' => 'required|integer|min:0|max:99',
            'score_b' => 'required|integer|min:0|max:99',
        ]);

        if (! EventMatch::isPossibleScore($data['score_a'], $data['score_b'])) {
            $message = 'That score is not possible: the game ends as soon as a team reaches '
                .EventMatch::POINTS_TO_WIN.' with a '.EventMatch::WIN_BY.'-point lead.';

            return $request->expectsJson()
                ? response()->json(['message' => $message, 'errors' => ['score_a' => [$message]]], 422)
                : back()->withErrors(['score_a' => $message]);
        }

        $wasComplete = $match->isComplete();
        $match->update($data);

        // The game just ended: free its players and draw the next game.
        $nextGameReady = ! $wasComplete && $match->isComplete() && $matchmaker->fill($event) > 0;

        if ($request->expectsJson()) {
            return response()->json([
                'score_a' => $match->score_a,
                'score_b' => $match->score_b,
                'complete' => $match->isComplete(),
                'winner' => $match->winnerLabel(),
                'next_game_ready' => $nextGameReady,
            ]);
        }

        return back()->with('message', match (true) {
            $nextGameReady => 'Game over! The next game is ready.',
            $match->isComplete() => 'Final score saved.',
            default => 'Score saved.',
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
