@include('admin.partials.header', ['title' => 'Manage events'])
<div class="admin-heading">
    <div>
        <p class="eyebrow eyebrow-dark">Control center</p>
        <h1 class="admin-title">Events</h1>
        <p class="admin-subtitle">Create events, manage matchups, and publish live scores.</p>
    </div>
    <a class="button button-dark" href="{{ route('admin.events.create') }}">New event <span aria-hidden="true">+</span></a>
</div>
@if(session('message'))<div class="admin-alert success">{{ session('message') }}</div>@endif
<div class="admin-stat-grid">
    <div class="admin-stat"><span>Upcoming events</span><strong>{{ $events->count() }}</strong></div>
    <div class="admin-stat"><span>Registered players</span><strong>{{ $events->sum(fn ($event) => $event->playerRegistrations->count()) }}</strong></div>
    <div class="admin-stat"><span>Capacity</span><strong>{{ $events->sum('capacity') }}</strong></div>
</div>
<section class="admin-card mt-8">
    <div class="admin-card-heading"><div><p class="eyebrow eyebrow-dark">Schedule</p><h2>All events</h2></div><a class="nav-link" href="{{ route('admin.bookings.index') }}">Manage bookings &#8594;</a></div>
    <div class="admin-event-list">
        @forelse($events as $event)
            <article class="admin-event-row">
                <div class="admin-event-date"><strong>{{ $event->date->format('d') }}</strong><span>{{ $event->date->format('M Y') }}</span></div>
                <div class="min-w-0"><p class="eyebrow eyebrow-dark">{{ \Carbon\Carbon::parse($event->time)->format('g:i A') }}</p><h3>{{ $event->title }}</h3><p>{{ $event->registrations() }} / {{ $event->capacity }} players registered</p></div>
                <div class="admin-actions"><a class="button button-outline" href="{{ route('events.show', $event) }}">Open</a><a class="button button-outline" href="{{ route('admin.events.edit', $event) }}">Edit</a><form method="POST" action="{{ route('admin.events.destroy', $event) }}">@csrf @method('DELETE')<button class="button button-danger" type="submit">Cancel</button></form></div>
            </article>
        @empty
            <div class="admin-empty"><p>No events have been created yet.</p><a class="button button-dark mt-4" href="{{ route('admin.events.create') }}">Create your first event</a></div>
        @endforelse
    </div>
</section>
<section class="admin-card mt-8">
    <div class="admin-card-heading"><div><p class="eyebrow eyebrow-dark">Player register</p><h2>Event registrations</h2></div><span class="admin-muted">{{ $events->sum(fn ($event) => $event->playerRegistrations->count()) }} total players</span></div>
    <div class="admin-registration-list">
        @forelse($events as $event)
            <article class="admin-registration-row">
                <div>
                    <p class="eyebrow eyebrow-dark">{{ $event->date->format('M j, Y') }}</p>
                    <h3>{{ $event->title }}</h3>
                    <p>{{ $event->playerRegistrations->count() }} / {{ $event->capacity }} registered</p>
                </div>
                <div class="admin-player-list">
                    @forelse($event->playerRegistrations as $registration)
                        <span class="admin-player-chip">{{ $registration->player_name }}</span>
                    @empty
                        <span class="admin-muted">No players registered yet.</span>
                    @endforelse
                </div>
                <a class="button button-outline" href="{{ route('events.show', $event) }}">Open event</a>
            </article>
        @empty
            <div class="admin-empty"><p>No events have been created yet.</p></div>
        @endforelse
    </div>
</section>
@include('admin.partials.footer')
