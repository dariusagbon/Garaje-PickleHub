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
<body class="min-w-[320px] overflow-x-hidden bg-[#f4f1e8] font-sans text-[#14201e]">
    <header class="sticky top-0 z-20 flex h-[76px] items-center justify-between border-b border-[#d7d3c7] bg-[#f4f1e8]/95 px-6 backdrop-blur sm:px-[7vw]">
        <a class="flex items-center gap-3" href="{{ url('/') }}" aria-label="PickleHub home"><img class="h-11 w-11 rounded-full object-cover" src="{{ asset('images/logo.jpeg') }}" alt="PickleHub logo"><span class="text-sm font-bold uppercase tracking-[2px]">Pickle<span class="text-[#c86a42]">Hub</span></span></a>
        <nav id="main-menu" class="absolute left-0 top-[76px] hidden w-full border-b border-[#d7d3c7] bg-[#f4f1e8] px-6 py-5 md:static md:flex md:w-auto md:items-center md:gap-9 md:border-0 md:p-0" aria-label="Primary navigation">@guest <a class="nav-link" href="#courts">Book a court</a><a class="nav-link" href="{{route('scoring')}}">Open scoreboard</a><a class="nav-link" href="#events">Events & scoring</a><a class="nav-link" href="#contact">Visit us</a><a class="nav-link" href="{{route('login')}}">Log in</a><a class="button button-dark md:hidden" href="#courts">Book a court</a>@else @if(auth()->user()->is_admin)<a class="nav-link" href="{{route('admin.dashboard')}}">Admin dashboard</a>@else <a class="nav-link" href="#courts">Book a court</a><a class="nav-link" href="{{route('scoring')}}">Open scoreboard</a><a class="nav-link" href="{{route('dashboard')}}">My dashboard</a><a class="button button-dark md:hidden" href="#courts">Book a court</a>@endif <span class="nav-user">Hi, {{ auth()->user()->name }}</span><form method="POST" action="{{route('logout')}}">@csrf<button class="nav-link">Log out</button></form>@endguest</nav>
        <a class="button button-dark hidden md:inline-flex" href="#courts">Book a court <span aria-hidden="true">&#8599;</span></a><button id="menu-toggle" class="border-0 bg-transparent p-2 md:hidden" type="button" aria-label="Open menu" aria-expanded="false"><span class="menu-line"></span><span class="menu-line"></span><span class="menu-line"></span></button>
    </header>
    <main>
        <section id="home" class="hero relative overflow-hidden px-6 py-20 sm:px-[7vw] lg:min-h-[580px] lg:py-32"><div class="hero-image absolute inset-0"></div><div class="relative z-[1] max-w-3xl"><p class="eyebrow">Davao's home for pickleball</p><h1 class="mt-4 max-w-2xl text-6xl font-bold uppercase leading-[.9] tracking-[-3px] text-[#f8f5ed] sm:text-8xl">Play the<br><em class="font-normal text-[#e1aa62]">long game.</em></h1><p class="mt-7 max-w-md text-base leading-7 text-[#d9d6cc]">One pro court, live scoring, and a better reason to get outside today.</p><a class="button button-gold mt-8" href="#courts">Book a court <span aria-hidden="true">&#8594;</span></a></div><div class="relative z-[1] mt-20 flex flex-wrap gap-8 border-t border-white/20 pt-5 text-[#d9d6cc] lg:absolute lg:bottom-10 lg:left-[7vw] lg:right-[7vw] lg:mt-0"><span><strong class="block text-xl text-[#f8f5ed]">01</strong> tournament court</span><span><strong class="block text-xl text-[#f8f5ed]">07:00–00:00</strong> open daily</span><span><strong class="block text-xl text-[#f8f5ed]">4.9 / 5</strong> player rating</span></div></section>
        <section id="courts" class="mx-auto max-w-6xl px-6 py-20 sm:px-10">
            <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end">
                <div>
                    <p class="eyebrow eyebrow-dark">Reserve your next rally</p>
                    <h2 class="section-title">Courts & availability</h2>
                    <p class="mt-4 max-w-xl text-sm leading-6 text-[#59645e]">Choose a day to see every court's schedule. Booking hours run from <strong>7:00 AM to 12:00 AM</strong>.</p>
                </div>
                <div class="availability-legend" aria-label="Availability legend">
                    <span><i class="legend-dot available"></i>Available</span>
                    <span><i class="legend-dot booked"></i>Fully booked</span>
                </div>
            </div>
            <div class="calendar-shell mt-10">
                <div class="calendar-header">
                    <button id="previous-month" class="calendar-arrow" type="button" aria-label="Previous month">&#8592;</button>
                    <div><p class="eyebrow eyebrow-dark">Select a booking day</p><h3 id="calendar-month" class="calendar-month"></h3></div>
                    <button id="next-month" class="calendar-arrow" type="button" aria-label="Next month">&#8594;</button>
                </div>
                <div id="calendar-grid" class="calendar-grid" role="grid" aria-label="Booking calendar"></div>
            </div>
            <div class="schedule-panel mt-6">
                <div class="flex flex-col justify-between gap-3 border-b border-[#d7d3c7] pb-5 sm:flex-row sm:items-center">
                    <div><p class="eyebrow eyebrow-dark">Daily schedule</p><h3 id="selected-date" class="mt-2 font-[Space_Grotesk] text-2xl font-bold uppercase"></h3></div>
                    <div class="flex flex-wrap items-center gap-3">
                        <p id="availability-summary" class="text-sm text-[#59645e]"></p>
                        <button id="review-booking" class="button button-dark" type="button" disabled>Review booking</button>
                    </div>
                </div>
                <div id="schedule-grid" class="schedule-grid mt-5" aria-live="polite"></div>
            </div>
        </section>
        <section id="events" class="border-y border-[#d7d3c7] bg-[#e4e1d7] px-6 py-20 sm:px-[7vw]"><div class="mx-auto max-w-6xl"><p class="eyebrow eyebrow-dark">Events & scoring</p><h2 class="section-title">Live matches.<br><em class="font-normal text-[#c86a42]">Real results.</em></h2><p class="mt-5 max-w-md leading-7 text-[#59645e]">Register for an event, view randomized matchups, and follow the live score.</p><div class="mt-10 grid gap-4 sm:grid-cols-2">@forelse($events as $event)<article class="event-card"><p class="eyebrow eyebrow-dark">{{$event->date->format('l')}} · {{\Carbon\Carbon::parse($event->time)->format('g:i A')}}</p><h3><a href="{{route('events.show',$event)}}">{{$event->title}}</a></h3><p>{{$event->description}}</p><span class="event-count">{{$event->registrations()}} / {{$event->capacity}} registered</span><a class="button button-outline mt-4" href="{{route('events.show',$event)}}">View event & scoring</a></article>@empty <p>No upcoming events yet.</p>@endforelse</div></div></section>
        <section id="book" class="mx-auto max-w-3xl px-6 py-20 text-center sm:px-10"><p class="eyebrow eyebrow-dark">Ready when you are</p><h2 class="section-title">Your next game<br>starts here.</h2><p class="mx-auto mt-5 max-w-md leading-7 text-[#59645e]">Select one or more available hours in the booking calendar, then review your reservation before confirming.</p><div id="booking-confirmation" class="mt-8 hidden border border-[#9eb3a2] bg-[#e4eee5] p-5 text-left"></div></section>
        <div id="booking-modal" class="booking-modal fixed inset-0 z-50 hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="booking-modal-title">
            <div class="booking-modal-card">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="eyebrow eyebrow-dark">Review reservation</p><h2 id="booking-modal-title" class="mt-2 font-[Space_Grotesk] text-2xl font-bold uppercase">Confirm your hours</h2><p id="booking-hours-summary" class="mt-2 text-sm text-[#59645e]"></p></div>
                    <button id="close-booking-modal" class="calendar-arrow" type="button" aria-label="Close booking dialog">&times;</button>
                </div>
                <form id="guest-booking-form" class="mt-6 space-y-4">
                    <label class="block text-left text-xs font-bold uppercase tracking-[1px]" for="guest-name">Your name</label>
                    <input id="guest-name" name="guest_name" required maxlength="120" class="w-full border border-[#d7d3c7] bg-[#f4f1e8] p-3" placeholder="Your name">
                    <label class="block text-left text-xs font-bold uppercase tracking-[1px]" for="guest-email">Email address</label>
                    <input id="guest-email" name="guest_email" required type="email" class="w-full border border-[#d7d3c7] bg-[#f4f1e8] p-3" placeholder="Email address">
                    <button class="button button-dark w-full" type="submit">Confirm booking</button>
                </form>
            </div>
        </div>
    </main>
    <footer id="contact" class="flex flex-col gap-5 bg-[#14201e] px-6 py-10 text-sm text-[#c5cbc4] sm:flex-row sm:items-center sm:justify-between sm:px-[7vw]"><span class="font-bold uppercase tracking-[2px] text-[#f4f1e8]">Pickle<span class="text-[#e1aa62]">Hub</span></span><span>J.P. Laurel Avenue · Davao City</span><a class="text-[#e1aa62]" href="mailto:hello@picklehub.ph">hello@picklehub.ph</a></footer>
</body>
</html>
