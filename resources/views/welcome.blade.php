<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Premium padel courts, coaching, and community at Garaje Padel Club.">
    <title>Garaje pickle Hub | Play Elevated</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-w-[320px] overflow-x-hidden bg-[#0a0f13] font-sans text-[#f1eee7]">
    <header class="absolute top-0 z-10 flex h-[74px] w-full items-center justify-between bg-[#091016] px-6 sm:px-[6vw]">
        <a class="flex items-center" href="{{ url('/') }}" aria-label="Garaje PickleHub home">
            <img class="h-14 w-14 rounded-full object-cover" src="{{ asset('images/logo.jpeg') }}" alt="Garaje Backyard Gamezone logo">
        </a>
        <nav class="ml-auto mr-12 hidden gap-[clamp(18px,2.7vw,38px)] md:flex" aria-label="Primary navigation">
            <a class="text-[9px] uppercase tracking-[1.5px] text-[#b69a67]" href="#home">Home</a>
            <a class="text-[9px] uppercase tracking-[1.5px] text-[#b69a67]" href="#courts">View Availability</a>
            <a class="text-[9px] uppercase tracking-[1.5px] text-[#b1b4af] hover:text-[#b69a67]" href="#contact">Contact</a>
        </nav>
        <a class="hidden border border-[#b69a67] px-[18px] py-3 text-[9px] uppercase tracking-[1.4px] md:block" href="#book">Book a court <span class="ml-3 text-[#b69a67]">&#8599;</span></a>
        <button class="block border-0 bg-transparent p-[5px] md:hidden" type="button" aria-label="Open menu"><span class="my-1 block h-px w-[18px] bg-[#f1eee7]"></span><span class="my-1 block h-px w-[18px] bg-[#f1eee7]"></span><span class="my-1 block h-px w-[18px] bg-[#f1eee7]"></span></button>
    </header>
    <main>
        <section id="home" class="relative flex min-h-[610px] bg-cover bg-center px-6 pb-[75px] pt-[186px] sm:px-[8vw]" style="background-image: url('{{ asset('images/background.jpg') }}')">
            <div class="absolute inset-0 bg-[rgba(5,9,12,.58)]"></div>
            <div class="relative z-[1] max-w-[410px]"><p class="mb-[13px] text-[9px] uppercase tracking-[2.5px] text-[#b69a67]">Premium padel club</p><h1 class="mb-[25px] text-[clamp(55px,7vw,92px)] font-medium uppercase leading-[.87] tracking-[-3px]">Play<br><em class="font-light not-italic">elevated.</em></h1><p class="mb-7 max-w-[270px] text-[13px] leading-[1.65] text-[#c3c4bd]">Top-tier courts, world-class facilities, and a community that shares your passion.</p><a class="inline-block border border-[#b69a67] bg-[#b69a67] px-[18px] py-3 text-[9px] font-bold uppercase tracking-[1.4px] text-[#0a0f13]" href="#book">Book a court <span class="ml-3">&#8594;</span></a></div>
            <a class="absolute bottom-[39px] left-6 z-[1] text-[8px] uppercase tracking-[1.5px] text-[#aaa9a1] sm:left-[8vw]" href="#club"><span class="mr-2 inline-grid h-5 w-5 place-items-center rounded-full border border-[#888]">&#8595;</span>Scroll to explore</a><span class="absolute bottom-10 right-6 z-[1] text-[8px] uppercase tracking-[1.5px] text-[#aaa9a1] sm:right-[8vw]">Davao city  / Est. 2026</span>
        </section>
        <section id="club" class="bg-[#0a0f13] px-6 py-[58px] sm:px-[8vw]">
            <div class="text-center">
                <p class="mb-[13px] text-[9px] uppercase tracking-[2.5px] text-[#b69a67]">Built for players</p>
                <h2 class="mb-[34px] text-xl font-medium uppercase tracking-[2px]">Premium facilities</h2>
            </div>
            <div id="courts" class="mx-auto grid max-w-[900px] grid-cols-2 gap-y-10 md:grid-cols-4 md:gap-y-0">
                <article class="border-r border-[rgba(241,238,231,.16)] px-6 text-center">
                    <span class="mx-auto mb-[18px] block h-[34px] w-[43px] border border-[#b69a67]"></span>
                    <h3 class="mb-[9px] text-[10px] uppercase tracking-[1px]">Premium courts</h3>
                    <p class="text-[10px] leading-[1.5] text-[#8b8e89]">Panoramic views, professional lighting & world-class surfaces.</p>
                </article>
                <article class="border-r-0 px-6 text-center md:border-r md:border-[rgba(241,238,231,.16)]">
                    <span class="mx-auto mb-[18px] block h-[34px] w-[43px] rounded-md border border-[#b69a67]"></span>
                    <h3 class="mb-[9px] text-[10px] uppercase tracking-[1px]">Club lounge</h3>
                    <p class="text-[10px] leading-[1.5] text-[#8b8e89]">Relax and recharge in our luxury lounge & cafe.</p>
                </article>
                <article class="border-r border-[rgba(241,238,231,.16)] px-6 text-center">
                    <span class="mx-auto mb-[18px] block h-[34px] w-[43px] border-y-2 border-[#b69a67]"></span>
                    <h3 class="mb-[9px] text-[10px] uppercase tracking-[1px]">Fitness area</h3>
                    <p class="text-[10px] leading-[1.5] text-[#8b8e89]">Performance training zone to elevate your game.</p>
                </article>
                <article id="membership" class="px-6 text-center">
                    <span class="mx-auto mb-[18px] block h-[34px] w-[43px] rounded-full border border-[#b69a67]"></span>
                    <h3 class="mb-[9px] text-[10px] uppercase tracking-[1px]">Community</h3>
                    <p class="text-[10px] leading-[1.5] text-[#8b8e89]">Join events, leagues & a community that plays together.</p>
                </article>
            </div>
        </section>
        <section id="gallery" class="bg-[#11171b] px-6 pb-[60px] sm:px-[6vw]"><div class="pt-[5px] text-center"><p class="mb-[13px] text-[9px] uppercase tracking-[2.5px] text-[#b69a67]">Experience padel</p><h2 class="mb-[22px] text-xl font-medium uppercase tracking-[2px]">More than a game</h2></div><div class="grid grid-cols-2 gap-[5px] md:grid-cols-4">@foreach ([['photo-1554068865-24cecd4e34b8', 'Racket and balls beside a court'], ['photo-1535131749006-b7f58c99034b', 'Warmly lit club lounge'], ['photo-1551698618-1dfe5d97d256', 'Outdoor court at sunset'], ['photo-1595435934249-5df7ed86e1c0', 'Padel racket and tennis balls']] as $image)<figure class="aspect-[1.13] overflow-hidden"><img class="h-full w-full object-cover transition duration-500 hover:scale-105" src="https://images.unsplash.com/{{ $image[0] }}?auto=format&fit=crop&w=900&q=85" alt="{{ $image[1] }}" loading="lazy"></figure>@endforeach</div></section>
        <section id="book" class="flex flex-col items-center px-5 py-[70px] text-center"><p class="mb-[13px] text-[9px] uppercase tracking-[2.5px] text-[#b69a67]">Your next match starts here</p><h2 class="mb-[25px] text-[clamp(25px,4vw,42px)] font-normal uppercase">Find your court time.</h2><a class="inline-block border border-[#b69a67] bg-[#b69a67] px-[18px] py-3 text-[9px] font-bold uppercase tracking-[1.4px] text-[#0a0f13]" href="#contact">Book a court <span class="ml-3">&#8594;</span></a></section>
    </main>
    <footer id="contact" class="flex flex-col items-center gap-3 border-t border-[rgba(241,238,231,.16)] px-6 py-6 text-center text-[9px] uppercase tracking-[1px] text-[#8b8e89] md:flex-row md:justify-between md:text-left"><span>Garaje Padel Club</span><span>Play elevated. / San Diego, CA</span><a class="text-[#b69a67]" href="mailto:hello@garajepadel.com">hello@garajepadel.com</a></footer>
</body>
</html>
