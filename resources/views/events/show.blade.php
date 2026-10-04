<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $event->title }} · PickleHub</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#f4f1e8] text-[#14201e]">
<main class="mx-auto max-w-5xl px-6 py-12 sm:px-10">
 <a class="nav-link" href="{{ url('/') }}">← Back to PickleHub</a>
 <header class="mt-8 border-b border-[#d7d3c7] pb-8"><p class="eyebrow eyebrow-dark">{{ $event->date->format('l, M j, Y') }} · {{ \Carbon\Carbon::parse($event->time)->format('g:i A') }}</p><h1 class="section-title">{{ $event->title }}</h1><p class="mt-4 max-w-2xl text-[#59645e]">{{ $event->description }}</p></header>
 @if(session('message'))<p class="mt-6 border border-[#9eb3a2] bg-[#e4eee5] p-4 text-sm">{{ session('message') }}</p>@endif
 @if($errors->any())<div class="mt-6 border border-[#c86a42] bg-[#f9e5da] p-4 text-sm">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 <div class="mt-10 grid gap-10 lg:grid-cols-2">
  <section><p class="eyebrow eyebrow-dark">Registration</p><h2 class="mt-2 font-[Space_Grotesk] text-2xl font-bold uppercase">{{ $event->playerRegistrations->count() }} / {{ $event->capacity }} players</h2>
   @if($event->isPast())
   <p class="mt-5 text-sm text-[#59645e]">This event has ended. Registration is closed.</p>
   @else
   @auth
   <form class="mt-5 space-y-3" method="POST" action="{{ route('events.register',$event) }}">@csrf<label class="block text-sm font-bold" for="player_name">Player name</label><input class="w-full border border-[#c9c6ba] bg-[#faf8f2] p-3" id="player_name" name="player_name" maxlength="100" required value="{{ old('player_name', optional($event->playerRegistrations->firstWhere('user_id',auth()->id()))?->player_name ?? auth()->user()->name) }}"><button class="button button-dark">{{ $event->playerRegistrations->firstWhere('user_id',auth()->id()) ? 'Update name' : 'Register' }}</button></form>
   @else <p class="mt-5 text-sm text-[#59645e]"><a class="underline" href="{{ route('login') }}">Log in</a> to register and choose your player name.</p>@endauth
   @endif
   @php
    $waiting = $event->playerRegistrations->reject(fn ($r) => $stats[$r->id]->active)->sortBy(fn ($r) => $stats[$r->id]->games)->values();
    $needed = max(0, \App\Services\Matchmaker::PLAYERS_PER_MATCH - $waiting->count());
   @endphp
   <h3 class="mt-10 font-[Space_Grotesk] text-xl font-bold uppercase">Registered players</h3><ul class="mt-3">@forelse($event->playerRegistrations as $registration)<li class="player-queue-row"><span>{{ $registration->player_name }}</span><span class="player-queue-meta">{{ $stats[$registration->id]->games }} {{ \Illuminate\Support\Str::plural('game', $stats[$registration->id]->games) }}<b class="queue-badge {{ $stats[$registration->id]->active ? 'playing' : 'waiting' }}">{{ $stats[$registration->id]->active ? 'In a game' : 'Waiting' }}</b></span></li>@empty<li class="py-2 text-sm text-[#59645e]">No players yet.</li>@endforelse</ul>
   @if($event->playerRegistrations->isNotEmpty() && ! $event->isPast())
   <div class="queue-card"><p class="eyebrow eyebrow-dark">Next game queue</p>
    @if($waiting->isEmpty())<p>Everyone is in a game right now. The next game is created as soon as a score is saved.</p>
    @else<p>{{ $waiting->count() }} waiting{{ $needed ? " · needs $needed more ".\Illuminate\Support\Str::plural('player', $needed)." to start" : '' }}. Players with the fewest games go first.</p><div class="queue-chips">@foreach($waiting as $registration)<span class="admin-player-chip">{{ $registration->player_name }} <small>{{ $stats[$registration->id]->games }}</small></span>@endforeach</div>@endif
   </div>
   @endif
  </section>
  <section><div class="flex items-end justify-between gap-4"><div><p class="eyebrow eyebrow-dark">Randomized doubles</p><h2 class="mt-2 font-[Space_Grotesk] text-2xl font-bold uppercase">Matches & results</h2></div>@if(auth()->user()?->is_admin && ! $event->isPast() && $event->matches->contains(fn ($m) => ! $m->isComplete()))<form method="POST" action="{{ route('admin.events.matches.randomize',$event) }}" onsubmit="return confirm('Reshuffle all games that have no score yet? Scored games are kept.')">@csrf<button class="button button-outline">Reshuffle unplayed games</button></form>@endif</div>
   <p class="mt-3 text-xs text-[#59645e]">Teams are drawn automatically as players register. Nobody plays a second game until everyone has played, unless there aren't enough fresh players to fill a team. Single-game scoring: first to 11, winning by 2.</p>
   <div class="mt-5 space-y-6">@forelse($event->matches as $match)<article class="scoreboard-card">
    <div class="flex flex-col justify-between gap-4 border-b border-[#d7d3c7] pb-5 sm:flex-row sm:items-center"><div><p class="eyebrow eyebrow-dark">Game {{ $loop->iteration }} · {{ $match->isComplete() ? 'Final' : 'Waiting for score' }}</p><p class="mt-2 text-sm font-bold text-[#59645e]">Single game · First to 11, win by 2</p></div><div class="score-result {{ $match->isComplete() ? 'complete' : '' }}">{{ $match->winnerLabel() ?? 'In progress' }}</div></div>
    <form class="score-form" method="POST" action="{{ route('events.matches.score',[$event,$match]) }}">@csrf @method('PATCH')
    <div class="scoreboard-teams mt-5 grid gap-4 md:grid-cols-2">
     <section class="score-team score-team-a"><p class="eyebrow eyebrow-dark">Team A</p><p class="mt-2 min-h-10 text-sm font-bold text-[#59645e]">{{ $match->teamA->pluck('player_name')->join(' & ') }}</p><div class="event-score-number"><input id="score-a-{{ $match->id }}" type="number" min="0" name="score_a" value="{{ $match->score_a ?? 0 }}" required></div><div class="score-control justify-center"><button type="button" data-score-target="score-a-{{ $match->id }}" data-score-change="-1" aria-label="Decrease Team A score">−</button><button type="button" data-score-target="score-a-{{ $match->id }}" data-score-change="1" aria-label="Increase Team A score">+</button></div></section>
     <section class="score-team score-team-b"><p class="eyebrow eyebrow-dark">Team B</p><p class="mt-2 min-h-10 text-sm font-bold text-[#59645e]">{{ $match->teamB->pluck('player_name')->join(' & ') }}</p><div class="event-score-number"><input id="score-b-{{ $match->id }}" type="number" min="0" name="score_b" value="{{ $match->score_b ?? 0 }}" required></div><div class="score-control justify-center"><button type="button" data-score-target="score-b-{{ $match->id }}" data-score-change="-1" aria-label="Decrease Team B score">−</button><button type="button" data-score-target="score-b-{{ $match->id }}" data-score-change="1" aria-label="Increase Team B score">+</button></div></section>
    </div>
    <div class="mt-5 border-t border-[#e8e4da] pt-5">
     <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end"><label class="scoring-field" for="score-pin-{{ $match->id }}">Scorekeeper PIN<input id="score-pin-{{ $match->id }}" type="password" name="score_pin" minlength="4" maxlength="32" required></label><button class="score-point-button mt-0 min-h-12 sm:w-48" type="submit">Save live score</button></div>
    </div></form>
   </article>@empty<p class="text-sm text-[#59645e]">The first doubles game is drawn automatically once {{ \App\Services\Matchmaker::PLAYERS_PER_MATCH }} players register.</p>@endforelse</div>
  </section>
 </div>
</main>
</body>
</html>
