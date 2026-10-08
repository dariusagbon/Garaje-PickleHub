<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Public pickleball scoreboard for singles and doubles matches.">
    <title>Open scoreboard | PickleHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-w-[320px] overflow-x-hidden bg-[#f5f3ff] font-sans text-[#1b1840]">

    {{-- ============================== Header ============================== --}}
    <header class="sticky top-0 z-20 flex h-[76px] items-center justify-between border-b border-[#ddd6fe] bg-[#f5f3ff]/95 px-6 backdrop-blur sm:px-[7vw]">
        <a class="flex items-center gap-3" href="{{ url('/') }}" aria-label="PickleHub home">
            <img class="h-11 w-11 rounded-full object-cover" src="{{ asset('images/logo.jpeg') }}" alt="PickleHub logo">
            <span class="text-sm font-bold uppercase tracking-[2px]">Pickle<span class="text-[#ff4d8d]">Hub</span></span>
        </a>

        <nav id="main-menu"
             class="absolute left-0 top-[76px] hidden w-full border-b border-[#ddd6fe] bg-[#f5f3ff] px-6 py-5 md:static md:flex md:w-auto md:items-center md:gap-9 md:border-0 md:p-0"
             aria-label="Primary navigation">
            <a class="nav-link" href="{{ url('/') }}#courts">Book a court</a>
            <a class="nav-link" href="{{ route('scoring') }}">Open scoreboard</a>
            <a class="nav-link" href="{{ url('/') }}#events">Events & scoring</a>
            <a class="nav-link" href="{{ url('/') }}#contact">Visit us</a>
        </nav>

        <button id="menu-toggle" class="border-0 bg-transparent p-2 md:hidden" type="button" aria-label="Open menu" aria-expanded="false">
            <span class="menu-line"></span>
            <span class="menu-line"></span>
            <span class="menu-line"></span>
        </button>
    </header>

    <main class="scoring-page mx-auto max-w-6xl px-6 py-12 sm:px-10 sm:py-16">
        <div class="max-w-2xl">
            <p class="eyebrow eyebrow-dark">Courtside scoring</p>
            <h1 class="section-title">Open scoreboard.</h1>
            <p class="mt-5 leading-7 text-[#57537a]">
                A simple side-out scoring board for games outside an event.
                Set the format, enter team names, and keep the phone courtside.
            </p>
        </div>

        {{-- ============================== Match setup ============================== --}}
        <section class="scoring-settings mt-10" aria-labelledby="scoring-settings-title">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="eyebrow eyebrow-dark">Match setup</p>
                    <h2 id="scoring-settings-title" class="mt-2 font-[Space_Grotesk] text-2xl font-bold uppercase">Choose your game</h2>
                </div>
                <span class="scorekeeper-badge">No login needed</span>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="scoring-field">
                    Team A name
                    <input id="team-a-name" maxlength="40" value="Team A">
                </label>
                <label class="scoring-field">
                    Team B name
                    <input id="team-b-name" maxlength="40" value="Team B">
                </label>
                <label class="scoring-field">
                    Game type
                    <select id="scoring-mode">
                        <option value="doubles">Doubles</option>
                        <option value="singles">Singles</option>
                    </select>
                </label>
                <label class="scoring-field">
                    Points to win
                    <select id="points-to-win">
                        <option value="11">11 points</option>
                        <option value="15">15 points</option>
                        <option value="21">21 points</option>
                    </select>
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-4">
                <label class="scoring-field scoring-field-inline">
                    Match format
                    <select id="match-format">
                        <option value="1">Best of 1</option>
                        <option value="3">Best of 3</option>
                        <option value="5">Best of 5</option>
                    </select>
                </label>
                <button id="start-scoring" class="button button-dark" type="button">Start match</button>
            </div>
        </section>

        {{-- ============================== Scoreboard ============================== --}}
        <section id="scoreboard" class="scoreboard-card mt-6" aria-live="polite">
            <div class="flex flex-col justify-between gap-4 border-b border-[#ddd6fe] pb-5 sm:flex-row sm:items-center">
                <div>
                    <p id="match-progress" class="eyebrow eyebrow-dark">Game 1 · Best of 1</p>
                    <p id="score-call" class="mt-2 text-sm font-bold text-[#57537a]">Team A 0 - Team B 0</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button id="undo-score" class="button button-outline" type="button" disabled>Undo last action</button>
                    <button id="fullscreen-toggle" class="button button-dark" type="button" aria-pressed="false">
                        <span aria-hidden="true">⛶</span> <span data-fullscreen-label>Full screen</span>
                    </button>
                </div>
            </div>

            <div id="match-status" class="scoreboard-status mt-5" role="status">Team A serves first · Server 2</div>

            <div class="scoreboard-teams mt-5 grid grid-cols-2 gap-2 sm:gap-4">
                <article class="score-team score-team-a">
                    <p id="score-team-a-label" class="eyebrow eyebrow-dark">Team A</p>
                    <div class="score-number-box"><strong id="score-team-a" class="scoreboard-number">0</strong></div>
                    <p id="serve-team-a" class="score-serve-label">Receiving</p>
                    <button class="score-point-button" data-score-team="A" type="button">Rally won by <span data-team-name="A">Team A</span></button>
                </article>
                <article class="score-team score-team-b">
                    <p id="score-team-b-label" class="eyebrow eyebrow-dark">Team B</p>
                    <div class="score-number-box"><strong id="score-team-b" class="scoreboard-number">0</strong></div>
                    <p id="serve-team-b" class="score-serve-label">Serving · Server 2 · Right court</p>
                    <button class="score-point-button" data-score-team="B" type="button">Rally won by <span data-team-name="B">Team B</span></button>
                </article>
            </div>

            <div class="scoreboard-stats mt-6 grid gap-3 text-sm text-[#57537a] sm:grid-cols-3">
                <div class="score-stat"><span>Games</span><strong id="games-score">0 - 0</strong></div>
                <div class="score-stat"><span>Serve points</span><strong id="serve-stats">0 - 0</strong></div>
                <div class="score-stat"><span>Return wins</span><strong id="return-stats">0 - 0</strong></div>
            </div>

            <div id="winner-banner" class="winner-banner mt-5 hidden" role="status"></div>

            {{-- Short message between games of a best-of-3/5 match --}}
            <div id="game-flash" class="game-flash" role="status" aria-live="polite" hidden></div>

            {{-- Match won: balloons + next-match options (resources/js/modules/scoreboard.js) --}}
            <div id="celebration" class="celebration" role="dialog" aria-modal="true" aria-labelledby="celebration-title" hidden>
                <div class="balloons" aria-hidden="true"></div>
                <div class="celebration-card">
                    <p class="eyebrow">Match over</p>
                    <h2 id="celebration-title" class="celebration-title">🏆 Team A wins!</h2>
                    <p id="celebration-score" class="celebration-score"></p>
                    <div class="celebration-actions">
                        <button id="rematch" class="button button-gold" type="button">Rematch <span aria-hidden="true">↻</span></button>
                        <button id="new-match" class="button celebration-outline" type="button">New match</button>
                    </div>
                    <button id="celebration-undo" class="celebration-undo" type="button">↶ Oops, undo the last rally</button>
                </div>
            </div>
        </section>

        {{-- ============================== Rules ============================== --}}
        <section class="mt-8 border-t border-[#ddd6fe] pt-6">
            <p class="eyebrow eyebrow-dark">Scoring guide</p>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-[#57537a]">
                Traditional side-out scoring: only the serving side scores.
                If the receiving side wins the rally, serve passes over with no score.
                In doubles, the first serving side starts with Server 2, then both servers rotate on side-outs.
            </p>
        </section>
    </main>
</body>
</html>
