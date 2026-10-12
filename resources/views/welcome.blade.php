<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Premium padel courts, coaching, and community at Garaje Padel Club.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Garaje pickle Hub | Play Elevated</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-w-[320px] overflow-x-hidden bg-[#eee4d1] font-sans text-[#173d2a]">

    {{-- ============================== Header ============================== --}}
    <header class="sticky top-0 z-20 flex h-[76px] items-center justify-between border-b border-[#d8cab1] bg-[#eee4d1]/95 px-6 backdrop-blur sm:px-[7vw]">
        <a class="flex shrink-0 items-center gap-3" href="{{ url('/') }}" aria-label="PickleHub home">
            <img class="h-11 w-11 rounded-full object-cover" src="{{ asset('images/logo.jpeg') }}" alt="PickleHub logo">
            <span class="text-sm font-bold uppercase tracking-[2px]">Pickle<span class="text-[#c25546]">Hub</span></span>
        </a>

        <nav id="main-menu"
             class="absolute left-0 top-[76px] hidden w-full border-b border-[#d8cab1] bg-[#eee4d1] px-6 py-5 lg:static lg:flex lg:w-auto lg:items-center lg:gap-6 lg:border-0 lg:p-0 xl:gap-8"
             aria-label="Primary navigation">
            @guest
                <a class="nav-link" href="#courts">Book a court</a>
                <a class="nav-link" href="{{ route('scoring') }}">Open scoreboard</a>
                <a class="nav-link" href="#events">Events & scoring</a>
                <a class="nav-link" href="#contact">Visit us</a>
                <a class="nav-link" href="{{ route('login') }}">Log in</a>
                <a class="button button-dark lg:hidden" href="#courts">Book a court</a>
            @else
                @if (auth()->user()->is_admin)
                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Admin dashboard</a>
                @else
                    <a class="nav-link" href="#courts">Book a court</a>
                    <a class="nav-link" href="{{ route('scoring') }}">Open scoreboard</a>
                    <a class="nav-link" href="{{ route('dashboard') }}">My dashboard</a>
                    <a class="button button-dark lg:hidden" href="#courts">Book a court</a>
                @endif
                <span class="nav-user">Hi, {{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="nav-link">Log out</button>
                </form>
            @endguest
        </nav>

        <a class="button button-dark hidden shrink-0 xl:inline-flex" href="#courts">
            Book a court <span aria-hidden="true">&#8599;</span>
        </a>
        <button id="menu-toggle" class="border-0 bg-transparent p-2 lg:hidden" type="button" aria-label="Open menu" aria-expanded="false">
            <span class="menu-line"></span>
            <span class="menu-line"></span>
            <span class="menu-line"></span>
        </button>
    </header>

    @include('partials.announcements')

    <main>
        {{-- ============================== Hero ============================== --}}
        <section id="home" class="hero relative overflow-hidden px-6 py-20 sm:px-[7vw] lg:min-h-[580px] lg:py-32">
            <div class="hero-image absolute inset-0" style="background-image: linear-gradient(90deg, rgba(23,61,42, .45), rgba(23,61,42, .15)), url('{{ asset('images/background.jpg') }}');"></div>

            <div class="relative z-[1] max-w-3xl">
                <p class="eyebrow">Davao's home for pickleball</p>
                <h1 class="mt-4 max-w-2xl text-6xl font-bold uppercase leading-[.9] tracking-[-3px] text-[#f3eadb] sm:text-8xl">
                    Play the<br><em class="font-normal text-[#e8968b]">long game.</em>
                </h1>
                <p class="mt-7 max-w-md text-base leading-7 text-[#cfc1a8]">
                    One pro court, live scoring, and a better reason to get outside today.
                </p>
                <a class="button button-gold mt-8" href="#courts">
                    Book a court <span aria-hidden="true">&#8594;</span>
                </a>
            </div>

            <div class="relative z-[1] mt-20 flex flex-wrap gap-8 border-t border-white/20 pt-5 text-[#cfc1a8] lg:absolute lg:bottom-10 lg:left-[7vw] lg:right-[7vw] lg:mt-0">
                <span><strong class="block text-xl text-[#f3eadb]">01</strong> tournament court</span>
                <span><strong class="block text-xl text-[#f3eadb]">07:00–00:00</strong> open daily</span>
                <span><strong class="block text-xl text-[#f3eadb]">4.9 / 5</strong> player rating</span>
            </div>
        </section>

        {{-- ============================== Court booking ============================== --}}
        <section id="courts" class="mx-auto max-w-6xl px-6 py-20 sm:px-10" data-reveal>
            <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                <div>
                    <p class="eyebrow eyebrow-dark">Reserve your next rally</p>
                    <h2 class="section-title">Courts & availability</h2>
                    <p class="mt-4 max-w-xl text-sm leading-6 text-[#43564a]">
                        Pick a day, tap the hours you want, and confirm in seconds.
                        Booking hours run from <strong>7:00 AM to 12:00 AM</strong>.
                    </p>
                    <div class="rate-cards" aria-label="Court rates">
                        <span class="rate-card"><i aria-hidden="true">☀</i><b>{{ \App\Models\Booking::peso(config('booking.day_rate')) }}</b><small>per hour · 8 AM – 5 PM</small></span>
                        <span class="rate-card rate-card-evening"><i aria-hidden="true">☾</i><b>{{ \App\Models\Booking::peso(config('booking.evening_rate')) }}</b><small>per hour · 6 PM onwards</small></span>
                    </div>
                    <ol class="booking-steps" aria-label="How booking works">
                        <li><b>1</b>Pick a day</li>
                        <li><b>2</b>Choose hours</li>
                        <li><b>3</b>Confirm</li>
                    </ol>
                </div>

                <div class="availability-legend" aria-label="Availability legend">
                    <span><i class="legend-dot available"></i>Available</span>
                    <span><i class="legend-dot selected"></i>Selected</span>
                    <span><i class="legend-dot booked"></i>Booked</span>
                </div>
            </div>

            <div class="booking-layout mt-10">
                {{-- Month calendar (filled in by resources/js/app.js) --}}
                <div class="calendar-shell">
                    <div class="calendar-header">
                        <button id="previous-month" class="calendar-arrow" type="button" aria-label="Previous month">&#8592;</button>
                        <div>
                            <p class="eyebrow eyebrow-dark">Select a booking day</p>
                            <h3 id="calendar-month" class="calendar-month"></h3>
                        </div>
                        <button id="next-month" class="calendar-arrow" type="button" aria-label="Next month">&#8594;</button>
                    </div>
                    <div id="calendar-grid" class="calendar-grid" role="grid" aria-label="Booking calendar"
                         data-day-rate="{{ config('booking.day_rate') }}"
                         data-evening-rate="{{ config('booking.evening_rate') }}"
                         data-evening-starts="{{ config('booking.evening_starts_at') }}"></div>
                    <p class="calendar-tip">Tip: hold <kbd>Shift</kbd> and click two hours to select the whole range.</p>
                </div>

                {{-- Hour picker for the selected day --}}
                <div class="schedule-panel">
                    <div class="flex flex-col justify-between gap-3 border-b border-[#d8cab1] pb-5 sm:flex-row sm:items-center">
                        <div>
                            <p class="eyebrow eyebrow-dark">Daily schedule</p>
                            <h3 id="selected-date" class="mt-2 font-display text-2xl font-bold uppercase"></h3>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <p id="availability-summary" class="text-sm text-[#43564a]"></p>
                            <button id="review-booking" class="button button-dark" type="button" disabled>Review booking</button>
                        </div>
                    </div>
                    <div id="schedule-grid" class="schedule-grid mt-5" aria-live="polite"></div>
                </div>
            </div>
        </section>

        {{-- ============================== Events ============================== --}}
        <section id="events" data-reveal class="border-y border-[#d8cab1] bg-[#ddd0b9] px-6 py-20 sm:px-[7vw]">
            <div class="mx-auto max-w-6xl">
                <p class="eyebrow eyebrow-dark">Events & scoring</p>
                <h2 class="section-title">
                    Live matches.<br><em class="font-normal text-[#c25546]">Real results.</em>
                </h2>
                <p class="mt-5 max-w-md leading-7 text-[#43564a]">
                    Register for an event, view randomized matchups, and follow the live score.
                </p>

                <div class="mt-10 grid gap-4 sm:grid-cols-2">
                    @forelse ($events as $event)
                        <article class="event-card">
                            <p class="eyebrow eyebrow-dark">
                                {{ $event->date->format('l') }} · {{ \Carbon\Carbon::parse($event->time)->format('g:i A') }}
                            </p>
                            <h3><a href="{{ route('events.show', $event) }}">{{ $event->title }}</a></h3>
                            <p>{{ $event->description }}</p>
                            {{-- Registration counts are for logged-in users only. --}}
                            {{-- No whitespace between the count and the button: the button sits directly after it. --}}
                            @auth
                                <span class="event-count">{{ max(0, $event->capacity - $event->registrations()) }} of {{ $event->capacity }} spots left</span><a
                                   class="button button-outline mt-4" href="{{ route('events.show', $event) }}">View event & scoring</a>
                            @else
                                <span class="event-count">{{ $event->capacity }} player event</span><a
                                   class="button button-outline mt-4" href="{{ route('events.show', $event) }}">View event & scoring</a>
                            @endauth
                        </article>
                    @empty
                        <p>No upcoming events yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ============================== Call to action ============================== --}}
        <section id="book" data-reveal class="mx-auto max-w-3xl px-6 py-20 text-center sm:px-10">
            <p class="eyebrow eyebrow-dark">Ready when you are</p>
            <h2 class="section-title">Your next game<br>starts here.</h2>
            <p class="mx-auto mt-5 max-w-md leading-7 text-[#43564a]">
                Select one or more available hours in the booking calendar, then review your reservation before confirming.
            </p>
            <div id="booking-confirmation" class="booking-confirmation mt-8 hidden" role="status"></div>
        </section>

        {{-- ============================== Booking dialog ============================== --}}
        <div id="booking-modal" class="booking-modal fixed inset-0 z-50 hidden"
             aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="booking-modal-title">
            <div class="booking-modal-card">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="eyebrow eyebrow-dark">Review reservation</p>
                        <h2 id="booking-modal-title" class="mt-2 font-display text-2xl font-bold uppercase">Confirm your hours</h2>
                    </div>
                    <button id="close-booking-modal" class="calendar-arrow" type="button" aria-label="Close booking dialog">&times;</button>
                </div>

                <div id="booking-hours-summary" class="booking-summary"></div>

                <form id="guest-booking-form" class="mt-6 space-y-4">
                    <label class="block text-left text-xs font-bold uppercase tracking-[1px]" for="guest-name">Your name</label>
                    <input id="guest-name" name="guest_name" required maxlength="120"
                           class="w-full border border-[#d8cab1] bg-[#eee4d1] p-3" placeholder="Your name">

                    <label class="block text-left text-xs font-bold uppercase tracking-[1px]" for="guest-email">Email address</label>
                    <input id="guest-email" name="guest_email" required type="email" autocomplete="email"
                           class="w-full border border-[#d8cab1] bg-[#eee4d1] p-3" placeholder="Email address">
                    <p class="text-left text-xs text-[#65776b]">Your receipt will be sent to this email.</p>

                    <div id="booking-error" class="booking-error hidden" role="alert"></div>

                    <button id="booking-submit" class="button button-dark w-full" type="submit">
                        <span class="button-spinner" aria-hidden="true"></span>
                        <span class="button-label">Confirm booking</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Sticky bar that appears once hours are selected --}}
        <div id="booking-bar" class="booking-bar" aria-hidden="true" aria-live="polite">
            <div>
                <strong data-bar-count></strong>
                <span data-bar-detail></span>
            </div>
            <div class="flex items-center gap-2">
                <button class="booking-bar-clear" type="button" data-bar-clear>Clear</button>
                <button class="button button-gold" type="button" data-bar-review>
                    <span>Review<span class="hidden sm:inline"> booking</span></span> &#8594;
                </button>
            </div>
        </div>
    </main>

    {{-- ============================== Footer ============================== --}}
    <footer id="contact" class="flex flex-col gap-5 bg-[#173d2a] px-6 py-10 text-sm text-[#c3d9c3] sm:flex-row sm:items-center sm:justify-between sm:px-[7vw]">
        <span class="font-bold uppercase tracking-[2px] text-[#eee4d1]">Pickle<span class="text-[#e8968b]">Hub</span></span>
        <span>Brgy. Mandug · Buhangin District · Davao City</span>
        <a class="text-[#e8968b]" href="mailto:hello@picklehub.ph">hello@picklehub.ph</a>
    </footer>
</body>
</html>
