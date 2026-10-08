@include('admin.partials.header', ['title' => 'Monthly report · '.$report->start->format('F Y')])
@use('Carbon\Carbon')
@use('Illuminate\Support\Str')
@php
    $hourLabel = fn ($hour) => Carbon::createFromTime($hour % 24)->format('g A');
    $maxPerHour = max(1, $perHour->max());
    $period = $report->start->format('M j').' – '.$report->end->format('M j, Y');
@endphp

{{-- ============================== Controls (not printed) ============================== --}}
<div class="report-toolbar no-print">
    <div class="flex items-center gap-2">
        <a class="calendar-arrow" href="{{ route('admin.reports.monthly', ['month' => $previousMonth]) }}" aria-label="Previous month">&#8592;</a>
        <form method="GET" action="{{ route('admin.reports.monthly') }}" class="flex items-center gap-2">
            <label class="sr-only" for="report-month">Month</label>
            <input id="report-month" class="report-month-input" type="month" name="month"
                   value="{{ $report->start->format('Y-m') }}" onchange="this.form.submit()">
            <noscript><button class="button button-outline" type="submit">Show</button></noscript>
        </form>
        <a class="calendar-arrow" href="{{ route('admin.reports.monthly', ['month' => $nextMonth]) }}" aria-label="Next month">&#8594;</a>
    </div>
    <button class="button button-dark" type="button" onclick="window.print()">
        Print report <span aria-hidden="true">&#9113;</span>
    </button>
</div>

<article class="report">

    {{-- ============================== Title ============================== --}}
    <header class="report-header">
        <div>
            <p class="eyebrow eyebrow-dark">PickleHub · Monthly report</p>
            <h1 class="admin-title">{{ $report->start->format('F Y') }}</h1>
            <p class="admin-subtitle">Court bookings and events for {{ $period }}.</p>
        </div>
        <dl class="report-meta">
            <div><dt>Generated</dt><dd>{{ now()->format('M j, Y g:i A') }}</dd></div>
            <div><dt>Prepared by</dt><dd>{{ auth()->user()->name }}</dd></div>
            <div><dt>Court</dt><dd>PickleHub Court · {{ count($report->hours) }} bookable hours a day</dd></div>
        </dl>
    </header>

    {{-- ============================== Summary ============================== --}}
    <section class="report-section report-keep-together">
        <h2 class="report-heading">Summary</h2>

        <div class="report-summary">
            <div class="report-summary-group">
                <h3>Court bookings</h3>
                <dl class="report-kpis">
                    <div>
                        <dt>Hours booked</dt>
                        <dd>{{ $bookingSummary['confirmed_hours'] }}<small>/ {{ $bookingSummary['capacity_hours'] }}</small></dd>
                    </div>
                    <div>
                        <dt>Utilisation</dt>
                        <dd>{{ $bookingSummary['utilisation'] }}%</dd>
                    </div>
                    <div>
                        <dt>Unique guests</dt>
                        <dd>{{ $bookingSummary['guests'] }}</dd>
                    </div>
                    <div>
                        <dt>Cancellations</dt>
                        <dd>{{ $bookingSummary['cancelled'] }}</dd>
                    </div>
                    <div>
                        <dt>Busiest day</dt>
                        <dd class="text-base">{{ $bookingSummary['busiest_day']?->format('D, M j') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Most popular hour</dt>
                        <dd class="text-base">
                            {{ $bookingSummary['busiest_hour'] !== null ? $hourLabel($bookingSummary['busiest_hour']) : '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="report-summary-group">
                <h3>Events</h3>
                <dl class="report-kpis">
                    <div>
                        <dt>Events held</dt>
                        <dd>{{ $eventSummary['events'] }}</dd>
                    </div>
                    <div>
                        <dt>Registrations</dt>
                        <dd>{{ $eventSummary['registrations'] }}</dd>
                    </div>
                    <div>
                        <dt>Unique players</dt>
                        <dd>{{ $eventSummary['players'] }}</dd>
                    </div>
                    <div>
                        <dt>Games played</dt>
                        <dd>{{ $eventSummary['games'] }}</dd>
                    </div>
                    <div>
                        <dt>Average fill</dt>
                        <dd>{{ $eventSummary['fill_rate'] }}%</dd>
                    </div>
                    <div>
                        <dt>Most popular event</dt>
                        <dd class="text-base">{{ $eventSummary['top_event']?->title ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- ============================== Daily court usage ============================== --}}
    <section class="report-section">
        <h2 class="report-heading">Court usage by day</h2>
        <div class="report-table-wrap">
<table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Hours booked</th>
                    <th class="w-1/2">Utilisation</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($perDay as $day)
                    <tr @class(['is-weekend' => $day['date']->isWeekend()])>
                        <td>{{ $day['date']->format('D, M j') }}</td>
                        <td class="text-right tabular-nums">{{ $day['hours'] }}</td>
                        <td>
                            <span class="report-bar"><i style="width: {{ $day['utilisation'] }}%"></i></span>
                            <span class="report-bar-label">{{ $day['utilisation'] }}%</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="text-right tabular-nums">{{ $bookingSummary['confirmed_hours'] }}</td>
                    <td>{{ $bookingSummary['utilisation'] }}% of {{ $bookingSummary['capacity_hours'] }} available hours</td>
                </tr>
            </tfoot>
        </table>
</div>
    </section>

    {{-- ============================== Popular hours + top guests ============================== --}}
    <div class="report-columns">
        <section class="report-section">
            <h2 class="report-heading">Bookings by start time</h2>
            <div class="report-table-wrap">
<table class="report-table">
                <thead>
                    <tr>
                        <th>Hour</th>
                        <th class="text-right">Times booked</th>
                        <th class="w-1/2"><span class="sr-only">Share</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($perHour as $hour => $count)
                        <tr>
                            <td>{{ $hourLabel($hour) }}</td>
                            <td class="text-right tabular-nums">{{ $count }}</td>
                            <td><span class="report-bar"><i style="width: {{ round($count / $maxPerHour * 100) }}%"></i></span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
</div>
        </section>

        <section class="report-section">
            <h2 class="report-heading">Top guests</h2>
            @if ($topGuests->isEmpty())
                <p class="report-empty">No confirmed bookings this month.</p>
            @else
                <div class="report-table-wrap">
<table class="report-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guest</th>
                            <th class="text-right">Hours</th>
                            <th class="text-right">Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topGuests as $guest)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $guest['name'] }}</strong>
                                    <small class="block text-[#65776b]">{{ $guest['email'] }}</small>
                                </td>
                                <td class="text-right tabular-nums">{{ $guest['hours'] }}</td>
                                <td class="text-right tabular-nums">{{ $guest['days'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
</div>
            @endif
        </section>
    </div>

    {{-- ============================== Events ============================== --}}
    <section class="report-section report-page-break">
        <h2 class="report-heading">Events</h2>

        @if ($events->isEmpty())
            <p class="report-empty">No events scheduled this month.</p>
        @else
            <div class="report-table-wrap">
<table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th class="text-right">Registered</th>
                        <th class="text-right">Capacity</th>
                        <th class="text-right">Fill</th>
                        <th class="text-right">Games played</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <td>{{ $event->date->format('D, M j') }} · {{ Carbon::parse($event->time)->format('g:i A') }}</td>
                            <td><strong>{{ $event->title }}</strong></td>
                            <td class="text-right tabular-nums">{{ $event->player_registrations_count }}</td>
                            <td class="text-right tabular-nums">{{ $event->capacity }}</td>
                            <td class="text-right tabular-nums">
                                {{ $event->capacity ? round($event->player_registrations_count / $event->capacity * 100) : 0 }}%
                            </td>
                            <td class="text-right tabular-nums">
                                {{ $event->completed_matches_count }}@if ($event->matches_count > $event->completed_matches_count)<small class="text-[#65776b]"> / {{ $event->matches_count }} drawn</small>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
</div>

            <h3 class="report-subheading">Who joined</h3>
            <div class="report-rosters">
                @foreach ($events as $event)
                    <div class="report-roster">
                        <h4>
                            {{ $event->title }}
                            <small>{{ $event->date->format('M j') }} · {{ $event->player_registrations_count }} {{ Str::plural('player', $event->player_registrations_count) }}</small>
                        </h4>
                        @if ($event->playerRegistrations->isEmpty())
                            <p class="report-empty">Nobody registered.</p>
                        @else
                            <ol>
                                @foreach ($event->playerRegistrations as $registration)
                                    <li>
                                        {{ $registration->player_name }}
                                        @if ($registration->user && $registration->user->name !== $registration->player_name)
                                            <small>({{ $registration->user->name }})</small>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ============================== Booking log ============================== --}}
    <section class="report-section report-page-break">
        <h2 class="report-heading">Booking log <small>{{ $bookings->count() }} {{ Str::plural('entry', $bookings->count()) }}</small></h2>

        @if ($bookings->isEmpty())
            <p class="report-empty">No bookings this month.</p>
        @else
            <div class="report-table-wrap">
<table class="report-table report-table-dense">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Guest</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr @class(['is-cancelled' => $booking->status === 'cancelled'])>
                            <td>{{ $booking->booking_date->format('D, M j') }}</td>
                            <td>{{ $hourLabel($booking->hour) }} – {{ $hourLabel($booking->hour + 1) }}</td>
                            <td>{{ $booking->guest_name }}</td>
                            <td>{{ $booking->guest_email }}</td>
                            <td><span class="admin-status {{ $booking->status }}">{{ $booking->status }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
</div>
        @endif
    </section>

    {{-- ============================== Sign-off (useful on paper) ============================== --}}
    <footer class="report-signoff">
        <div><span></span>Prepared by</div>
        <div><span></span>Reviewed by</div>
        <div><span></span>Date</div>
    </footer>
</article>

@include('admin.partials.footer')
