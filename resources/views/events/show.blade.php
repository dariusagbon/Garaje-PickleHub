@use('App\Services\Matchmaker')
@use('Carbon\Carbon')
@use('Illuminate\Support\Str')
@php
    $myRegistration = $event->playerRegistrations->firstWhere('user_id', auth()->id());

    $registered = $event->playerRegistrations->count();
    $spotsLeft = max(0, $event->capacity - $registered);
    $fill = $event->capacity ? min(100, round($registered / $event->capacity * 100)) : 0;
    $perMatch = Matchmaker::PLAYERS_PER_MATCH;

    // Players not in an unscored game, fewest games first: they fill the next game.
    $waiting = $event->playerRegistrations
        ->reject(fn ($registration) => $stats[$registration->id]->active)
        ->sortBy(fn ($registration) => $stats[$registration->id]->games)
        ->values();
    $needed = max(0, $perMatch - $waiting->count());

    $canReshuffle = auth()->user()?->is_admin
        && ! $event->isPast()
        && $event->matches->contains(fn ($match) => ! $match->isComplete());

    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $event->title }} · PickleHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f1e8] text-[#14201e]">
<main class="mx-auto max-w-6xl px-6 py-10 sm:px-10 sm:py-12">
    <a class="nav-link" href="{{ url('/') }}">← Back to PickleHub</a>

    {{-- ============================== Event header ============================== --}}
    <header class="ev-hero">
        <div class="min-w-0">
            <p class="eyebrow">
                {{ $event->isPast() ? 'Event ended' : ($event->date->isToday() ? 'Today' : 'Upcoming event') }}
            </p>
            <h1 class="ev-hero-title">{{ $event->title }}</h1>
            @if ($event->description)
                <p class="ev-hero-description">{{ $event->description }}</p>
            @endif
        </div>

        <dl class="ev-facts">
            <div>
                <dt>Date</dt>
                <dd>{{ $event->date->format('D, M j') }}</dd>
            </div>
            <div>
                <dt>Start</dt>
                <dd>{{ Carbon::parse($event->time)->format('g:i A') }}</dd>
            </div>
            <div>
                <dt>Players</dt>
                <dd>{{ $registered }}<small>/ {{ $event->capacity }}</small></dd>
            </div>
            <div>
                <dt>Spots left</dt>
                <dd>{{ $event->isPast() ? '—' : $spotsLeft }}</dd>
            </div>
            <div class="ev-facts-meter">
                <span class="ev-meter" aria-hidden="true"><i style="width: {{ $fill }}%"></i></span>
                <small>{{ $fill }}% full</small>
            </div>
        </dl>
    </header>

    @if (session('message'))
        <p class="ev-alert ev-alert-success" role="status">{{ session('message') }}</p>
    @endif

    @if ($errors->any())
        <div class="ev-alert ev-alert-error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">

        {{-- ============================== Registration ============================== --}}
        <section class="ev-panel">
            <div class="ev-panel-head">
                <div>
                    <p class="eyebrow eyebrow-dark">Registration</p>
                    <h2>Join the event</h2>
                </div>
                <span @class(['ev-status', 'is-full' => ! $spotsLeft || $event->isPast()])>
                    {{ $event->isPast() ? 'Closed' : ($spotsLeft ? 'Open' : 'Full') }}
                </span>
            </div>

            <div class="ev-count">
                <strong>{{ $registered }}</strong>
                <span>of {{ $event->capacity }} players registered</span>
            </div>
            <span class="ev-meter ev-meter-lg" aria-hidden="true"><i style="width: {{ $fill }}%"></i></span>

            @if ($event->isPast())
                <p class="ev-note">This event has ended. Registration is closed.</p>
            @else
                @auth
                    @if ($myRegistration)
                        <p class="ev-joined">
                            <span aria-hidden="true">✓</span>
                            You're in as <strong>{{ $myRegistration->player_name }}</strong>
                        </p>
                    @endif

                    @if ($myRegistration || $spotsLeft)
                        <form class="ev-join-form" method="POST" action="{{ route('events.register', $event) }}">
                            @csrf
                            <label for="player_name">{{ $myRegistration ? 'Change your player name' : 'Your player name' }}</label>
                            <div class="ev-join-row">
                                <input id="player_name" name="player_name" maxlength="100" required
                                       value="{{ old('player_name', $myRegistration?->player_name ?? auth()->user()->name) }}">
                                <button class="button button-dark">{{ $myRegistration ? 'Update name' : 'Register' }}</button>
                            </div>
                        </form>
                    @else
                        <p class="ev-note">This event is full.</p>
                    @endif
                @else
                    <div class="ev-login-cta">
                        <p>Log in to register and choose the name shown on the scoreboard.</p>
                        <div class="flex flex-wrap gap-2">
                            <a class="button button-dark" href="{{ route('login') }}">Log in to join</a>
                            <a class="button button-outline" href="{{ route('register') }}">Create account</a>
                        </div>
                    </div>
                @endauth
            @endif

            {{-- Registered players --}}
            <h3 class="ev-subhead">Registered players <span>{{ $registered }}</span></h3>
            @if ($event->playerRegistrations->isEmpty())
                <div class="ev-empty">
                    <span class="ev-empty-icon" aria-hidden="true">🏓</span>
                    <p><strong>No players yet.</strong> Be the first to join!</p>
                </div>
            @else
                <ul class="ev-players">
                    @foreach ($event->playerRegistrations as $registration)
                        @php $playerStats = $stats[$registration->id]; @endphp
                        <li @class(['is-me' => $registration->is($myRegistration)])>
                            <span class="ev-avatar" aria-hidden="true">{{ $initials($registration->player_name) }}</span>
                            <span class="min-w-0 flex-1">
                                <strong>{{ $registration->player_name }}</strong>
                                <small>{{ $playerStats->games }} {{ Str::plural('game', $playerStats->games) }}</small>
                            </span>
                            <b class="queue-badge {{ $playerStats->active ? 'playing' : 'waiting' }}">
                                {{ $playerStats->active ? 'In a game' : 'Waiting' }}
                            </b>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($event->playerRegistrations->isNotEmpty() && ! $event->isPast())
                <div class="queue-card">
                    <p class="eyebrow eyebrow-dark">Next game queue</p>

                    @if ($waiting->isEmpty())
                        <p>Everyone is in a game right now. The next game is created as soon as a score is saved.</p>
                    @else
                        <p>
                            {{ $waiting->count() }} waiting{{ $needed ? " · needs $needed more ".Str::plural('player', $needed).' to start' : '' }}.
                            Players with the fewest games go first.
                        </p>
                        <div class="queue-chips">
                            @foreach ($waiting as $registration)
                                <span class="admin-player-chip">
                                    {{ $registration->player_name }} <small>{{ $stats[$registration->id]->games }}</small>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </section>

        {{-- ============================== Matches ============================== --}}
        <section class="ev-panel">
            <div class="ev-panel-head">
                <div>
                    <p class="eyebrow eyebrow-dark">Randomized doubles</p>
                    <h2>Matches & results</h2>
                </div>

                @if ($canReshuffle)
                    <form method="POST" action="{{ route('admin.events.matches.randomize', $event) }}"
                          onsubmit="return confirm('Reshuffle games that have not started yet? Games in progress and finished games are kept.')">
                        @csrf
                        <button class="button button-outline">Reshuffle unplayed games</button>
                    </form>
                @endif
            </div>

            <ul class="ev-rules">
                <li><span aria-hidden="true">⚄</span><p><strong>Auto teams</strong>Drawn at random as players register.</p></li>
                <li><span aria-hidden="true">↻</span><p><strong>Fair rotation</strong>Everyone plays before anyone plays twice.</p></li>
                <li><span aria-hidden="true">11</span><p><strong>One game</strong>First to 11, win by 2.</p></li>
            </ul>

            <div class="mt-6 space-y-6">
                @forelse ($event->matches as $match)
                    <article @class(['scoreboard-card', 'is-final' => $match->isComplete()])>
                        <div class="flex flex-col justify-between gap-4 border-b border-[#d7d3c7] pb-5 sm:flex-row sm:items-center">
                            <div>
                                <p class="eyebrow eyebrow-dark">
                                    Game {{ $loop->iteration }} · <span data-game-state>{{ $match->isComplete() ? 'Final' : 'Live' }}</span>
                                </p>
                                <p class="mt-2 text-sm font-bold text-[#59645e]">Single game · First to 11, win by 2</p>
                            </div>
                            <div @class(['score-result', 'complete' => $match->isComplete()]) data-game-result>
                                {{ $match->winnerLabel() ?? 'In progress' }}
                            </div>
                        </div>

                        {{-- Scores save automatically (resources/js/modules/live-score.js). --}}
                        <form class="score-form" method="POST" action="{{ route('events.matches.score', [$event, $match]) }}" data-live-score>
                            @csrf
                            @method('PATCH')

                            <div class="scoreboard-teams mt-5 grid gap-4 md:grid-cols-2">
                                @foreach (['a' => $match->teamA, 'b' => $match->teamB] as $side => $players)
                                    @php
                                        $team = 'Team '.strtoupper($side);
                                        $inputId = "score-{$side}-{$match->id}";
                                    @endphp
                                    <section class="score-team score-team-{{ $side }}">
                                        <p class="eyebrow eyebrow-dark">{{ $team }}</p>
                                        <p class="mt-2 min-h-10 text-sm font-bold text-[#59645e]">
                                            {{ $players->pluck('player_name')->join(' & ') }}
                                        </p>
                                        <div class="event-score-number">
                                            <input id="{{ $inputId }}" type="number" min="0" name="score_{{ $side }}"
                                                   value="{{ $match->{'score_'.$side} ?? 0 }}" required>
                                        </div>
                                        <div class="score-control justify-center">
                                            <button type="button" data-score-target="{{ $inputId }}" data-score-change="-1" aria-label="Decrease {{ $team }} score">−</button>
                                            <button type="button" data-score-target="{{ $inputId }}" data-score-change="1" aria-label="Increase {{ $team }} score">+</button>
                                        </div>
                                    </section>
                                @endforeach
                            </div>

                            <div class="score-save-bar">
                                <p class="score-save-status" data-save-status role="status" aria-live="polite">
                                    <span class="score-save-dot" aria-hidden="true"></span>
                                    <span data-save-text>Scores save automatically</span>
                                </p>
                                <noscript>
                                    <button class="button button-dark" type="submit">Save score</button>
                                </noscript>
                            </div>
                        </form>
                    </article>
                @empty
                    {{-- Four seats that fill up as players register; the first game is drawn when all are taken. --}}
                    <div class="ev-first-game">
                        <div class="ev-seats" aria-hidden="true">
                            @for ($seat = 0; $seat < $perMatch; $seat++)
                                @php $player = $event->playerRegistrations[$seat] ?? null; @endphp
                                <span @class(['ev-seat', 'is-filled' => $player])>
                                    {{ $player ? $initials($player->player_name) : '?' }}
                                </span>
                                @if ($seat === 1)
                                    <em>vs</em>
                                @endif
                            @endfor
                        </div>
                        <p class="ev-first-game-title">
                            {{ $event->isPast() ? 'No games were played.' : 'Waiting for the first game' }}
                        </p>
                        @unless ($event->isPast())
                            <p class="ev-first-game-text">
                                {{ min($registered, $perMatch) }} of {{ $perMatch }} players ready.
                                The first doubles game is drawn automatically once {{ $perMatch }} players register.
                            </p>
                        @endunless
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</main>
</body>
</html>
