<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventMatch;
use App\Services\Matchmaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    public function score(Request $request, Event $event, EventMatch $match, Matchmaker $matchmaker)
    {
        abort_unless($match->event_id === $event->id, 404);

        $data = $request->validate([
            'score_pin' => 'required|string|max:32',
            'score_a' => 'required|integer|min:0',
            'score_b' => 'required|integer|min:0',
        ]);

        if (! $event->score_pin || ! Hash::check($data['score_pin'], $event->score_pin)) {
            return back()
                ->withErrors(['score_pin' => 'The scorekeeper PIN is incorrect.'])
                ->withInput();
        }
        unset($data['score_pin']);

        $high = max($data['score_a'], $data['score_b']);
        $low = min($data['score_a'], $data['score_b']);
        if ($high < 11 || $high - $low < 2) {
            return back()->withErrors([
                'score_a' => 'A game must be won by at least 2 points and reach 11 points.',
            ]);
        }

        $match->update($data);
        $created = $matchmaker->fill($event);

        return back()->with('message', $created
            ? 'Score saved. The next game is ready!'
            : 'Score saved.');
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
        $data = $request->validate($this->rules(pinRequired: true));
        $data['score_pin'] = Hash::make($data['score_pin']);

        Event::create($data);

        return redirect()->route('admin.events.index');
    }

    public function edit(Event $event)
    {
        return view('admin.events.form', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate($this->rules(pinRequired: false));

        // A blank PIN on the edit form keeps the current one.
        if (! empty($data['score_pin'])) {
            $data['score_pin'] = Hash::make($data['score_pin']);
        } else {
            unset($data['score_pin']);
        }

        $event->update($data);

        return redirect()->route('admin.events.index');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return back();
    }

    /** Reshuffle games that have no score yet (admin only). */
    public function randomize(Event $event, Matchmaker $matchmaker)
    {
        if ($event->isPast()) {
            return back()->withErrors(['event' => 'This event has already ended.']);
        }

        $matchmaker->reshuffle($event);

        return back()->with('message', 'Games without a score were reshuffled. Scored games were kept.');
    }

    private function rules(bool $pinRequired): array
    {
        return [
            'title' => 'required|max:255',
            'date' => 'required|date',
            'time' => 'required',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'score_pin' => ($pinRequired ? 'required' : 'nullable').'|string|min:4|max:32',
        ];
    }
}
