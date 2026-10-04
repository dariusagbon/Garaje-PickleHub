@include('admin.partials.header', ['title' => 'Dashboard'])
@use('Carbon\Carbon')
@use('Illuminate\Support\Str')
@php
    $hourLabel = fn ($hour) => Carbon::createFromTime($hour % 24)->format('g A');
    $dayLabel = fn ($date) => $date->isToday() ? 'Today' : ($date->isTomorrow() ? 'Tomorrow' : $date->format('l'));
    $capacity = count($hours);
    $peak = $forecast->sortByDesc('hours')->first();
    $liveEvent = $upcomingEvents->firstWhere('live_matches_count', '>', 0);
    $activityIcons = ['booking' => '◷', 'cancelled' => '✕', 'registration' => '★'];
@endphp

<div class="admin-heading">
    <div>
        <p class="eyebrow eyebrow-dark" data-greeting>Welcome back</p>
        <h1 class="admin-title">Dashboard</h1>
        <p class="admin-subtitle">{{ now()->format('l, F j') }} · Here's what's happening at PickleHub.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a class="button button-outline" href="{{ route('admin.reports.monthly') }}">Monthly report</a>
        <a class="button button-outline" href="{{ route('admin.bookings.index') }}">Bookings</a>
        <a class="button button-dark" href="{{ route('admin.events.create') }}">
            New event <span aria-hidden="true">+</span>
        </a>
    </div>
</div>

{{-- ============================== Key numbers ============================== --}}
<section class="admin-kpis" aria-label="Key numbers">
    <a class="admin-kpi" href="{{ route('admin.bookings.index') }}">
        <span>Court today</span>
        <strong>{{ $stats['today_hours'] }}<small>/ {{ $capacity }} hrs</small></strong>
        <i class="dash-meter" aria-hidden="true"><i style="--fill: {{ $stats['today_rate'] }}%"></i></i>
        <em>{{ $stats['today_rate'] }}% booked</em>
    </a>

    <a class="admin-kpi" href="{{ route('admin.bookings.index') }}">
        <span>Next 7 days</span>
        <strong>{{ $stats['week_hours'] }}<small>hrs</small></strong>
        <i class="dash-meter" aria-hidden="true"><i style="--fill: {{ $stats['week_rate'] }}%"></i></i>
        <em>{{ $stats['week_rate'] }}% of court time</em>
    </a>

    <a class="admin-kpi" href="{{ route('admin.events.index') }}">
        <span>Upcoming events</span>
        <strong>{{ $stats['upcoming_events'] }}</strong>
        <em>{{ $stats['players'] }} players registered</em>
    </a>

    <a @class(['admin-kpi', 'is-live' => $stats['live_games']])
       href="{{ $liveEvent ? route('events.show', $liveEvent) : route('admin.events.index') }}">
        <span>Games waiting for a score</span>
        <strong>{{ $stats['live_games'] }}</strong>
        <em>{{ $stats['members'] }} {{ Str::plural('member', $stats['members']) }} signed up</em>
    </a>
</section>

{{-- ============================== Today's court ============================== --}}
<section class="admin-card mt-8">
    <div class="admin-card-heading">
        <div>
            <p class="eyebrow eyebrow-dark">Today · PickleHub Court</p>
            <h2>Court schedule</h2>
        </div>
        <div class="availability-legend">
            <span><i class="legend-dot selected"></i>Booked</span>
            <span><i class="legend-dot available"></i>Open</span>
        </div>
    </div>

    <div class="court-timeline" role="list" aria-label="Today's court schedule">
        @foreach ($hours as $hour)
            @php
                $booking = $todayBookings[$hour] ?? null;
                $status = $booking ? 'booked by '.$booking->guest_name : 'open';
            @endphp
            <div @class([
                    'court-hour',
                    $booking ? 'booked' : 'open',
                    'past' => $hour < now()->hour,
                    'now' => $hour === now()->hour,
                 ])
                 role="listitem" tabindex="0"
                 aria-label="{{ $hourLabel($hour) }}: {{ $status }}">
                <span class="court-hour-bar"></span>
                <span class="court-hour-label">{{ $hourLabel($hour) }}</span>
                <span class="chart-tip">
                    <b>{{ $hourLabel($hour) }} – {{ $hourLabel($hour + 1) }}</b>
                    {{ $booking ? $booking->guest_name : 'Open' }}
                    @if ($booking)
                        <small>{{ $booking->guest_email }}</small>
                    @endif
                </span>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-xs text-[#788078]">
        {{ $stats['today_hours'] ? $stats['today_hours'].' of '.$capacity.' hours booked today.' : 'No bookings today yet.' }}
        Hover or tab through an hour to see who booked it.
    </p>
</section>

<div class="admin-dash-grid mt-8">

    {{-- ============================== 14-day bookings chart ============================== --}}
    <section class="admin-card">
        <div class="admin-card-heading">
            <div>
                <p class="eyebrow eyebrow-dark">Next 14 days</p>
                <h2>Booked court hours</h2>
            </div>
            @if ($peak && $peak['hours'])
                <span class="admin-muted">Busiest: {{ $peak['date']->format('D, M j') }} · {{ $peak['hours'] }} hrs</span>
            @endif
        </div>

        <div class="bar-chart" style="--max: {{ $capacity }}">
            <div class="bar-chart-axis" aria-hidden="true">
                <span>{{ $capacity }}h</span>
                <span>{{ intdiv($capacity, 2) }}h</span>
                <span>0</span>
            </div>

            <div class="bar-chart-plot" role="img" aria-label="Booked hours per day for the next 14 days. Details in the table below.">
                @foreach ($forecast as $day)
                    @php
                        // Only today and the busiest day get a number above the bar.
                        $isPeak = $peak && $day['hours'] && $day['date']->equalTo($peak['date']);
                    @endphp
                    <div @class(['bar-col', 'is-today' => $loop->first]) tabindex="0">
                        <span class="bar" style="--value: {{ $day['hours'] }}"></span>
                        @if ($loop->first || $isPeak)
                            <span class="bar-value" style="--value: {{ $day['hours'] }}">{{ $day['hours'] }}</span>
                        @endif
                        <span class="bar-label">
                            {{ $loop->first ? 'Today' : $day['date']->format('D') }}<small>{{ $day['date']->format('j') }}</small>
                        </span>
                        <span class="chart-tip">
                            <b>{{ $day['date']->format('D, M j') }}</b>
                            {{ $day['hours'] }} of {{ $capacity }} hours booked
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <details class="bar-chart-table text-xs text-[#59645e]">
            <summary class="cursor-pointer font-bold uppercase tracking-[1px]">View as table</summary>
            <table class="history-table mt-3 min-w-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Booked hours</th>
                        <th>Utilisation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($forecast as $day)
                        <tr>
                            <td>{{ $day['date']->format('D, M j') }}</td>
                            <td>{{ $day['hours'] }}</td>
                            <td>{{ round($day['hours'] / $capacity * 100) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    </section>

    {{-- ============================== Activity feed ============================== --}}
    <section class="admin-card">
        <div class="admin-card-heading">
            <div>
                <p class="eyebrow eyebrow-dark">Live feed</p>
                <h2>Recent activity</h2>
            </div>
        </div>

        <ol class="activity-list">
            @forelse ($activity as $item)
                <li>
                    <a class="activity-item" href="{{ $item->url }}">
                        <span class="activity-icon {{ $item->type }}" aria-hidden="true">{{ $activityIcons[$item->type] }}</span>
                        <span class="min-w-0 flex-1">
                            <strong>{{ $item->title }}</strong>
                            <span>{{ $item->detail }}</span>
                        </span>
                        <time datetime="{{ $item->at?->toIso8601String() }}">{{ $item->at?->diffForHumans(short: true) }}</time>
                    </a>
                </li>
            @empty
                <li class="admin-empty">No activity yet.</li>
            @endforelse
        </ol>
    </section>
</div>

{{-- ============================== Next events ============================== --}}
<section class="admin-card mt-8">
    <div class="admin-card-heading">
        <div>
            <p class="eyebrow eyebrow-dark">Schedule</p>
            <h2>Next events</h2>
        </div>
        <a class="nav-link" href="{{ route('admin.events.index') }}">All events &#8594;</a>
    </div>

    <div class="admin-event-list">
        @forelse ($upcomingEvents as $event)
            @php
                $fill = $event->capacity
                    ? min(100, round($event->player_registrations_count / $event->capacity * 100))
                    : 0;
            @endphp
            <article class="admin-event-row">
                <div class="admin-event-date">
                    <strong>{{ $event->date->format('d') }}</strong>
                    <span>{{ $event->date->format('M Y') }}</span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="eyebrow eyebrow-dark">
                        {{ $dayLabel($event->date) }} · {{ Carbon::parse($event->time)->format('g:i A') }}
                    </p>
                    <h3>{{ $event->title }}</h3>
                    <i class="dash-meter max-w-sm" aria-hidden="true"><i style="--fill: {{ $fill }}%"></i></i>
                    <p>
                        {{ $event->player_registrations_count }} / {{ $event->capacity }} players
                        · {{ $event->matches_count }} {{ Str::plural('game', $event->matches_count) }}
                        @if ($event->live_matches_count)
                            · <span class="font-bold text-[#c86a42]">{{ $event->live_matches_count }} waiting for a score</span>
                        @endif
                    </p>
                </div>

                <div class="admin-actions">
                    <a class="button button-outline" href="{{ route('events.show', $event) }}">Open</a>
                    <a class="button button-outline" href="{{ route('admin.events.edit', $event) }}">Edit</a>
                </div>
            </article>
        @empty
            <div class="admin-empty">
                <p>No upcoming events.</p>
                <a class="button button-dark mt-4" href="{{ route('admin.events.create') }}">Create an event</a>
            </div>
        @endforelse
    </div>
</section>

@include('admin.partials.footer')
