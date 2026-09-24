<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My dashboard · PickleHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f1e8] text-[#14201e]">
    <main class="player-dashboard mx-auto max-w-5xl px-6 py-10 sm:px-10 sm:py-14">
        <header class="flex flex-wrap items-center justify-between gap-5 border-b border-[#d7d3c7] pb-8">
            <div><a class="nav-link" href="{{ url('/') }}">← PickleHub home</a><p class="eyebrow eyebrow-dark mt-8">Player dashboard</p><h1 class="section-title">Hi, {{ auth()->user()->name }}.</h1><p class="mt-4 text-sm text-[#59645e]">Everything you need for your next game, in one place.</p></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="button button-outline">Log out</button></form>
        </header>
        <section class="mt-10"><p class="eyebrow eyebrow-dark">Find a game</p><h2 class="mt-2 font-[Space_Grotesk] text-3xl font-bold uppercase">Upcoming events</h2><div class="mt-5 space-y-3">@forelse($events as $event)<a class="player-event-row" href="{{ route('events.show', $event) }}"><span>{{ $event->date->format('M j') }} · {{ \Carbon\Carbon::parse($event->time)->format('g:i A') }}</span><strong>{{ $event->title }}</strong><span>View →</span></a>@empty<p class="player-empty">No upcoming events yet.</p>@endforelse</div></section>
        <section class="mt-12"><div class="flex items-end justify-between gap-4"><div><p class="eyebrow eyebrow-dark">Your events</p><h2 class="mt-2 font-[Space_Grotesk] text-2xl font-bold uppercase">Registered events</h2></div><span class="text-sm text-[#59645e]">{{ $registeredEvents->count() }} joined</span></div><div class="mt-5 grid gap-4 sm:grid-cols-2">@forelse($registeredEvents as $event)<a class="player-event-card" href="{{ route('events.show', $event) }}"><span class="eyebrow eyebrow-dark">{{ $event->date->format('M j, Y') }}</span><strong>{{ $event->title }}</strong><small>View players, matches, and scores →</small></a>@empty<p class="player-empty">You have not joined an event yet.</p>@endforelse</div></section>
        <section class="mt-12 border-t border-[#d7d3c7] pt-8"><p class="eyebrow eyebrow-dark">Quick actions</p><div class="mt-5 grid gap-5 sm:grid-cols-2"><a class="player-action-card" href="{{ url('/') }}#courts"><span class="eyebrow eyebrow-dark">Court booking</span><strong>Book a court <span aria-hidden="true">→</span></strong><small>Choose one or more available hours.</small></a><a class="player-action-card player-action-card-dark" href="{{ route('scoring') }}"><span class="eyebrow">Open scoreboard</span><strong>Keep score <span aria-hidden="true">→</span></strong><small>Start a public singles or doubles match.</small></a></div></section>
    </main>
</body>
</html>
