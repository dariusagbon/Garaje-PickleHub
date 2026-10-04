@include('admin.partials.header', ['title' => 'Event history'])
@use('Illuminate\Support\Str')

<div class="admin-heading">
    <div>
        <p class="eyebrow eyebrow-dark">Archive</p>
        <h1 class="admin-title">Event history</h1>
        <p class="admin-subtitle">Past events are hidden from players automatically. Review who joined each one here.</p>
    </div>
    <a class="button button-outline" href="{{ route('admin.events.index') }}">&#8592; Upcoming events</a>
</div>
<div class="admin-stat-grid">
    <div class="admin-stat">
        <span>Past events</span>
        <strong>{{ $events->count() }}</strong>
    </div>
    <div class="admin-stat">
        <span>Total players joined</span>
        <strong>{{ $events->sum(fn ($event) => $event->playerRegistrations->count()) }}</strong>
    </div>
    <div class="admin-stat">
        <span>Matches played</span>
        <strong>{{ $events->sum(fn ($event) => $event->matches->count()) }}</strong>
    </div>
</div>
<section class="admin-card mt-8">
    <div class="admin-card-heading">
        <div>
            <p class="eyebrow eyebrow-dark">Completed</p>
            <h2>Past events</h2>
        </div>
        @if ($events->isNotEmpty())
            <input id="history-search" class="history-search" type="search"
                   placeholder="Search events or players…" aria-label="Search events or players">
        @endif
    </div>
    <div class="admin-history-list">
        @forelse ($events as $event)
            @php
                // Everything the search box can match: title plus each player's names and email.
                $searchText = strtolower($event->title.' '.$event->playerRegistrations
                    ->map(fn ($r) => $r->player_name.' '.$r->user?->name.' '.$r->user?->email)
                    ->join(' '));
                $matchCount = $event->matches->count();
            @endphp
            <details class="history-event" data-history-search="{{ $searchText }}" @if ($loop->first) open @endif>
                <summary>
                    <div class="admin-event-date">
                        <strong>{{ $event->date->format('d') }}</strong>
                        <span>{{ $event->date->format('M Y') }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="eyebrow eyebrow-dark">
                            {{ $event->date->format('l') }} · {{ \Carbon\Carbon::parse($event->time)->format('g:i A') }}
                        </p>
                        <h3>{{ $event->title }}</h3>
                        <p>
                            {{ $event->playerRegistrations->count() }} / {{ $event->capacity }} players joined
                            · {{ $matchCount }} {{ Str::plural('match', $matchCount) }}
                        </p>
                    </div>
                    <span class="history-toggle" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="history-body">
                    @if ($event->playerRegistrations->isEmpty())
                        <p class="admin-muted">Nobody joined this event.</p>
                    @else
                        <div class="history-table-wrap">
                            <table class="history-table">
                                <thead><tr><th>#</th><th>Player name</th><th>Account</th><th>Email</th><th>Joined</th></tr></thead>
                                <tbody>
                                    @foreach ($event->playerRegistrations->sortBy('created_at')->values() as $registration)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><strong>{{ $registration->player_name }}</strong></td>
                                            <td>{{ $registration->user?->name ?? 'Deleted user' }}</td>
                                            <td>
                                                @if ($registration->user)
                                                    <a href="mailto:{{ $registration->user->email }}">{{ $registration->user->email }}</a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $registration->created_at?->format('M j, Y g:i A') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <a class="button button-outline mt-4" href="{{ route('events.show', $event) }}">View matches &amp; scores</a>
                </div>
            </details>
        @empty
            <div class="admin-empty"><p>No past events yet. Events appear here the day after they take place.</p></div>
        @endforelse
        <p id="history-no-results" class="admin-empty hidden">No events or players match your search.</p>
    </div>
</section>
@include('admin.partials.footer')
