@use('Carbon\Carbon')
@use('Illuminate\Support\Str')
@php
    $user = auth()->user();
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');

    $time = fn ($event) => Carbon::parse($event->time);
    $startsAt = fn ($event) => $event->date->format('Y-m-d').'T'.$time($event)->format('H:i:s');
    $dayLabel = fn ($date) => $date->isToday() ? 'Today' : ($date->isTomorrow() ? 'Tomorrow' : $date->format('l'));
    $hourText = fn ($hour) => Carbon::createFromTime($hour % 24)->format('g:i A');
    // Other players' names are admin-only, so games are described by team letter.
    $teamLetter = fn ($game) => $game->my_team === 1 ? 'A' : 'B';

    // Collapse booked hours into ranges such as "9:00 AM – 11:00 AM".
    $hourRanges = function ($hours) use ($hourText) {
        $ranges = [];
        foreach ($hours->sort()->values() as $hour) {
            if ($ranges && end($ranges)[1] === $hour) {
                $ranges[count($ranges) - 1][1] = $hour + 1;
            } else {
                $ranges[] = [$hour, $hour + 1];
            }
        }

        return collect($ranges)
            ->map(fn ($range) => $hourText($range[0]).' – '.$hourText($range[1]))
            ->join(', ');
    };
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My dashboard · PickleHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen min-w-[320px] overflow-x-hidden bg-[#eee4d1] font-sans text-[#173d2a]">

    {{-- ============================== Top bar ============================== --}}
    <header class="dash-topbar">
        <a class="flex items-center gap-3" href="{{ url('/') }}" aria-label="PickleHub home">
            <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('images/logo.jpeg') }}" alt="PickleHub logo">
            <span class="hidden text-sm font-bold uppercase tracking-[2px] sm:inline">Pickle<span class="text-[#c25546]">Hub</span></span>
        </a>
        <nav class="flex items-center gap-5 sm:gap-8" aria-label="Dashboard navigation">
            <a class="nav-link hidden sm:block" href="{{ url('/') }}#courts">Book a court</a>
            <a class="nav-link hidden sm:block" href="{{ route('scoring') }}">Scoreboard</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="nav-link" type="submit">Log out</button>
            </form>
        </nav>
    </header>

    @include('partials.announcements')

    {{-- ============================== Hero ============================== --}}
    <section class="dash-hero">
        <div class="dash-hero-inner">
            <div class="flex items-center gap-5">
                <span class="dash-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
                <div>
                    <p class="eyebrow" data-greeting>Welcome back</p>
                    <h1 class="dash-title">{{ $user->name }}</h1>
                    <p class="mt-2 text-sm text-[#c3d9c3]">Your games, events and court time in one place.</p>
                </div>
            </div>

            <div class="dash-next">
                <p class="eyebrow">Next up</p>

                @if ($nextEvent)
                    @php $status = $eventStatus[$nextEvent->id]; @endphp
                    <a class="dash-next-title" href="{{ route('events.show', $nextEvent) }}">{{ $nextEvent->title }}</a>
                    <p class="text-sm text-[#c3d9c3]">
                        {{ $nextEvent->date->format('l, M j') }} · {{ $time($nextEvent)->format('g:i A') }}
                    </p>

                    {{-- Ticks every second via resources/js/app.js --}}
                    <div class="dash-countdown" data-countdown="{{ $startsAt($nextEvent) }}" aria-live="polite">
                        <span><b data-unit="days">–</b><small>days</small></span>
                        <span><b data-unit="hours">–</b><small>hrs</small></span>
                        <span><b data-unit="minutes">–</b><small>min</small></span>
                        <span><b data-unit="seconds">–</b><small>sec</small></span>
                    </div>
                    <p class="dash-countdown-live hidden">Happening now — good luck out there!</p>

                    <p class="mt-3 text-xs text-[#c3d9c3]">
                        Playing as <strong class="text-[#eee4d1]">{{ $status->player_name }}</strong> ·
                        <span class="dash-chip {{ $status->active ? 'playing' : 'waiting' }}">
                            {{ $status->active ? 'In a game' : 'In the queue' }}
                        </span>
                    </p>
                @else
                    <p class="dash-next-title">No events yet</p>
                    <p class="text-sm text-[#c3d9c3]">Join an event below and you'll be drawn into games automatically.</p>
                    <a class="button button-gold mt-4" href="#open-events">Find an event &#8595;</a>
                @endif
            </div>
        </div>
    </section>

    <main class="mx-auto max-w-6xl px-6 pb-16 sm:px-10">

        {{-- ============================== Stats ============================== --}}
        <section class="dash-stats" aria-label="Your stats">
            <div class="dash-stat">
                <span>Events joined</span>
                <strong data-count-to="{{ $stats['events'] }}">{{ $stats['events'] }}</strong>
            </div>
            <div class="dash-stat">
                <span>Games played</span>
                <strong data-count-to="{{ $stats['games'] }}">{{ $stats['games'] }}</strong>
            </div>
            <div class="dash-stat">
                <span>Wins</span>
                <strong data-count-to="{{ $stats['wins'] }}">{{ $stats['wins'] }}</strong>
            </div>
            <div class="dash-stat">
                <span>Win rate</span>
                <strong>{{ $stats['win_rate'] === null ? '—' : $stats['win_rate'].'%' }}</strong>
                <i class="dash-meter" aria-hidden="true"><i style="--fill: {{ $stats['win_rate'] ?? 0 }}%"></i></i>
            </div>
        </section>

        {{-- Shown only while the player is in a game that has no score yet --}}
        @if ($currentGame)
            <a class="dash-live" href="{{ route('events.show', $currentGame->event) }}" data-reveal>
                <span class="dash-live-dot" aria-hidden="true"></span>
                <div class="min-w-0">
                    <p class="eyebrow">You're on court · {{ $currentGame->event->title }}</p>
                    <p class="dash-live-teams">
                        <strong>You're on Team {{ $teamLetter($currentGame) }}</strong>
                        <em>· Game {{ $currentGame->game_number }}</em>
                    </p>
                </div>
                <span class="dash-live-cta">Open game &#8594;</span>
            </a>
        @endif

        <div class="dash-grid">
            <div class="space-y-10">

                {{-- ============================== My upcoming events ============================== --}}
                <section data-reveal>
                    <div class="dash-section-head">
                        <div>
                            <p class="eyebrow eyebrow-dark">Your events</p>
                            <h2>Upcoming events</h2>
                        </div>
                        <span>{{ $myEvents->count() }} joined</span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($myEvents as $event)
                            @php $status = $eventStatus[$event->id]; @endphp
                            <a class="dash-event" href="{{ route('events.show', $event) }}">
                                <span class="dash-date">
                                    <b>{{ $event->date->format('d') }}</b>
                                    <small>{{ $event->date->format('M') }}</small>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="dash-event-meta">
                                        {{ $dayLabel($event->date) }} · {{ $time($event)->format('g:i A') }}
                                    </span>
                                    <strong class="dash-event-title">{{ $event->title }}</strong>
                                    <span class="dash-event-meta">
                                        as {{ $status->player_name }} · {{ $status->games }} {{ Str::plural('game', $status->games) }}
                                    </span>
                                </span>
                                <span class="dash-chip {{ $status->active ? 'playing' : 'waiting' }}">
                                    {{ $status->active ? 'In a game' : 'Queued' }}
                                </span>
                            </a>
                        @empty
                            <div class="player-empty">
                                You haven't joined an upcoming event.
                                <a class="font-bold text-[#c25546] underline" href="#open-events">Browse open events</a>.
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- ============================== Recent results ============================== --}}
                <section data-reveal>
                    <div class="dash-section-head">
                        <div>
                            <p class="eyebrow eyebrow-dark">Results</p>
                            <h2>Recent games</h2>
                        </div>
                        @if ($stats['games'])
                            <span>{{ $stats['wins'] }}W – {{ $stats['games'] - $stats['wins'] }}L</span>
                        @endif
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($recentGames as $game)
                            <a class="dash-result {{ $game->won ? 'won' : 'lost' }}" href="{{ route('events.show', $game->event) }}">
                                <span class="dash-result-badge">{{ $game->won ? 'W' : 'L' }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="dash-event-meta">{{ $game->event->title }} · {{ $game->event->date->format('M j') }}</span>
                                    <span class="block truncate text-sm">
                                        Game {{ $game->game_number }} · You played on Team {{ $teamLetter($game) }}
                                    </span>
                                </span>
                                <span class="dash-result-score">{{ $game->my_score }}<i>–</i>{{ $game->their_score }}</span>
                            </a>
                        @empty
                            <div class="player-empty">
                                No finished games yet. Your scores will show up here once a game is recorded.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-10">

                {{-- ============================== Court bookings ============================== --}}
                <section data-reveal>
                    <div class="dash-section-head">
                        <div>
                            <p class="eyebrow eyebrow-dark">Court time</p>
                            <h2>My bookings</h2>
                        </div>
                        <a class="nav-link" href="{{ url('/') }}#courts">Book &#8594;</a>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($bookings as $date => $dayBookings)
                            @php $day = Carbon::parse($date); @endphp
                            <div class="dash-booking">
                                <span class="dash-date dash-date-light">
                                    <b>{{ $day->format('d') }}</b>
                                    <small>{{ $day->format('M') }}</small>
                                </span>
                                <span class="min-w-0">
                                    <span class="dash-event-meta">
                                        {{ $dayLabel($day) }} · {{ $dayBookings->count() }} {{ Str::plural('hour', $dayBookings->count()) }}
                                    </span>
                                    <strong class="block text-sm">{{ $hourRanges($dayBookings->pluck('hour')) }}</strong>
                                    <span class="dash-event-meta">{{ $dayBookings->first()->court }}</span>
                                </span>
                            </div>
                        @empty
                            <div class="player-empty">No upcoming court bookings under {{ $user->email }}.</div>
                        @endforelse
                    </div>
                </section>

                {{-- ============================== Events to join ============================== --}}
                <section id="open-events" data-reveal>
                    <div class="dash-section-head">
                        <div>
                            <p class="eyebrow eyebrow-dark">Find a game</p>
                            <h2>Open events</h2>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($openEvents as $event)
                            @php
                                $joined = $event->player_registrations_count;
                                $left = max(0, $event->capacity - $joined);
                                $fill = $event->capacity ? round($joined / $event->capacity * 100) : 100;
                            @endphp
                            <a class="dash-open-event" href="{{ route('events.show', $event) }}">
                                <span class="flex items-start justify-between gap-3">
                                    <span class="min-w-0">
                                        <span class="dash-event-meta">
                                            {{ $event->date->format('D, M j') }} · {{ $time($event)->format('g:i A') }}
                                        </span>
                                        <strong class="dash-event-title">{{ $event->title }}</strong>
                                    </span>
                                    <span class="dash-chip {{ $left ? 'open' : 'full' }}">{{ $left ? 'Join' : 'Full' }}</span>
                                </span>
                                <i class="dash-meter mt-3" aria-hidden="true"><i style="--fill: {{ $fill }}%"></i></i>
                                <span class="dash-event-meta mt-1">
                                    {{ $joined }} / {{ $event->capacity }} players ·
                                    {{ $left ? $left.' '.Str::plural('spot', $left).' left' : 'event full' }}
                                </span>
                            </a>
                        @empty
                            <div class="player-empty">No other upcoming events right now. Check back soon!</div>
                        @endforelse
                    </div>
                </section>

                {{-- ============================== Shortcuts ============================== --}}
                <section class="grid gap-3" data-reveal>
                    <a class="player-action-card" href="{{ url('/') }}#courts">
                        <span class="eyebrow eyebrow-dark">Court booking</span>
                        <strong>Book a court <span aria-hidden="true">&#8594;</span></strong>
                        <small>Choose one or more available hours.</small>
                    </a>
                    <a class="player-action-card player-action-card-dark" href="{{ route('scoring') }}">
                        <span class="eyebrow">Open scoreboard</span>
                        <strong>Keep score <span aria-hidden="true">&#8594;</span></strong>
                        <small>Start a singles or doubles match.</small>
                    </a>
                </section>
            </aside>
        </div>
    </main>
</body>
</html>
