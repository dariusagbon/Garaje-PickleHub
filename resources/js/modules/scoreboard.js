// Open scoreboard page (/scoring), driven by scoring-reducer.js.
// Also handles full screen, the between-games message, and the match-won
// celebration (balloons) with Rematch / New match buttons.

import {
    createInitialState,
    scoringReducer,
} from '../scoring-reducer.js';

const scoringBoard = document.querySelector('#scoreboard');
if (scoringBoard) {
    let scoringState = null;
    let teamNames = { A: 'Team A', B: 'Team B' };
    let startingTeam = 'A';

    const scoringElement = (selector) => document.querySelector(selector);
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const currentServingPlayer = (state) => {
        if (state.mode === 'singles') return '';
        return ` · Server ${state.server}`;
    };
    const courtSide = (state) => (state.score[state.servingTeam] % 2 === 0 ? 'Right court' : 'Left court');

    function renderScoreboard() {
        if (!scoringState) return;
        const { score, servingTeam, matchWinner, gamesWon, stats } = scoringState;
        const other = servingTeam === 'A' ? 'B' : 'A';
        const call = `${teamNames[servingTeam]} ${score[servingTeam]} - ${score[other]}${scoringState.mode === 'doubles' ? ` - ${scoringState.server}` : ''}`;
        scoringElement('#score-team-a-label').textContent = teamNames.A;
        scoringElement('#score-team-b-label').textContent = teamNames.B;
        document.querySelectorAll('[data-team-name]').forEach((label) => {
            label.textContent = teamNames[label.dataset.teamName];
        });
        scoringElement('#score-team-a').textContent = score.A;
        scoringElement('#score-team-b').textContent = score.B;
        scoringElement('#score-call').textContent = matchWinner
            ? `${teamNames[matchWinner]} wins the match`
            : `Call: ${call}`;
        scoringElement('#match-progress').textContent = `Game ${scoringState.gameNumber} · Best of ${scoringState.bestOf}`;
        scoringElement('#match-status').textContent = matchWinner
            ? `${teamNames[matchWinner]} wins the match`
            : `${teamNames[servingTeam]} serving${currentServingPlayer(scoringState)} · ${courtSide(scoringState)}`;
        scoringElement('#serve-team-a').textContent = servingTeam === 'A'
            ? `Serving${currentServingPlayer(scoringState)} · ${courtSide(scoringState)}`
            : 'Receiving';
        scoringElement('#serve-team-b').textContent = servingTeam === 'B'
            ? `Serving${currentServingPlayer(scoringState)} · ${courtSide(scoringState)}`
            : 'Receiving';
        document.querySelectorAll('.score-team').forEach((panel) => {
            panel.classList.toggle('is-serving', !matchWinner && panel.classList.contains(`score-team-${servingTeam.toLowerCase()}`));
        });
        scoringElement('#games-score').textContent = `${gamesWon.A} - ${gamesWon.B}`;
        scoringElement('#serve-stats').textContent = `${stats.servePoints.A} - ${stats.servePoints.B}`;
        scoringElement('#return-stats').textContent = `${stats.returnWins.A} - ${stats.returnWins.B}`;
        scoringElement('#undo-score').disabled = scoringState.history.length === 0;
        document.querySelectorAll('[data-score-team]').forEach((button) => {
            button.disabled = Boolean(matchWinner);
        });
        const winnerBanner = scoringElement('#winner-banner');
        winnerBanner.textContent = matchWinner ? `${teamNames[matchWinner]} wins · ${gamesWon.A} - ${gamesWon.B}` : '';
        winnerBanner.classList.toggle('hidden', !matchWinner);
    }

    function startScoring(firstServer = 'A') {
        startingTeam = firstServer;
        teamNames = {
            A: scoringElement('#team-a-name').value.trim() || 'Team A',
            B: scoringElement('#team-b-name').value.trim() || 'Team B',
        };
        scoringState = createInitialState({
            mode: scoringElement('#scoring-mode').value,
            pointsToWin: Number(scoringElement('#points-to-win').value),
            bestOf: Number(scoringElement('#match-format').value),
            startingTeam,
        });
        hideCelebration();
        renderScoreboard();
    }

    /*
    |----------------------------------------------------------------------
    | Full screen (with a "fill the window" fallback for iPhone Safari)
    |----------------------------------------------------------------------
    */

    const fullscreenButton = scoringElement('#fullscreen-toggle');
    const canFullscreen = Boolean(scoringBoard.requestFullscreen || scoringBoard.webkitRequestFullscreen);
    let wakeLock = null;

    const isFullscreen = () => Boolean(document.fullscreenElement || document.webkitFullscreenElement)
        || scoringBoard.classList.contains('is-pseudo-fullscreen');

    async function keepScreenAwake(on) {
        try {
            if (on && 'wakeLock' in navigator) wakeLock = await navigator.wakeLock.request('screen');
            if (!on && wakeLock) {
                await wakeLock.release();
                wakeLock = null;
            }
        } catch {
            // Not supported or not allowed: the screen may dim as usual.
        }
    }

    function updateFullscreenButton() {
        const on = isFullscreen();
        scoringBoard.classList.toggle('is-fullscreen', on);
        fullscreenButton?.setAttribute('aria-pressed', String(on));
        const label = fullscreenButton?.querySelector('[data-fullscreen-label]');
        if (label) label.textContent = on ? 'Exit full screen' : 'Full screen';
        keepScreenAwake(on);
    }

    async function enterFullscreen() {
        if (canFullscreen) {
            try {
                await (scoringBoard.requestFullscreen?.() ?? scoringBoard.webkitRequestFullscreen());
                return;
            } catch {
                // Fall through to the window-filling fallback.
            }
        }
        scoringBoard.classList.add('is-pseudo-fullscreen');
        document.body.classList.add('overflow-hidden');
        updateFullscreenButton();
    }

    async function exitFullscreen() {
        if (document.fullscreenElement || document.webkitFullscreenElement) {
            await (document.exitFullscreen?.() ?? document.webkitExitFullscreen());
        }
        scoringBoard.classList.remove('is-pseudo-fullscreen');
        document.body.classList.remove('overflow-hidden');
        updateFullscreenButton();
    }

    fullscreenButton?.addEventListener('click', () => (isFullscreen() ? exitFullscreen() : enterFullscreen()));
    document.addEventListener('fullscreenchange', updateFullscreenButton);
    document.addEventListener('webkitfullscreenchange', updateFullscreenButton);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && scoringBoard.classList.contains('is-pseudo-fullscreen')) exitFullscreen();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && isFullscreen()) keepScreenAwake(true);
    });

    /*
    |----------------------------------------------------------------------
    | Between games, and the match-won celebration
    |----------------------------------------------------------------------
    */

    const celebration = scoringElement('#celebration');
    const gameFlash = scoringElement('#game-flash');
    let flashTimer = null;

    function showGameFlash(winner, finishedGame) {
        gameFlash.textContent = `Game ${finishedGame} → ${teamNames[winner]} · Next game: ${teamNames[scoringState.servingTeam]} serves first`;
        gameFlash.hidden = false;
        clearTimeout(flashTimer);
        flashTimer = setTimeout(() => { gameFlash.hidden = true; }, 3500);
    }

    function releaseBalloons() {
        const box = celebration.querySelector('.balloons');
        box.innerHTML = '';
        if (reducedMotion) return;
        const colours = ['#c86a42', '#e1aa62', '#6f9d7d', '#f4f1e8', '#d97757', '#9eb3a2'];
        for (let i = 0; i < 22; i += 1) {
            const balloon = document.createElement('span');
            balloon.className = 'balloon';
            balloon.style.setProperty('--x', `${Math.random() * 100}%`);
            balloon.style.setProperty('--size', `${42 + Math.random() * 34}px`);
            balloon.style.setProperty('--delay', `${Math.random() * 2.5}s`);
            balloon.style.setProperty('--duration', `${6 + Math.random() * 4}s`);
            balloon.style.setProperty('--sway', `${(Math.random() - 0.5) * 120}px`);
            balloon.style.setProperty('--colour', colours[i % colours.length]);
            box.appendChild(balloon);
        }
    }

    function showCelebration() {
        const { matchWinner, gamesWon, score, bestOf } = scoringState;
        const loser = matchWinner === 'A' ? 'B' : 'A';
        scoringElement('#celebration-title').textContent = `🏆 ${teamNames[matchWinner]} wins!`;
        scoringElement('#celebration-score').textContent = bestOf > 1
            ? `Games ${gamesWon[matchWinner]}–${gamesWon[loser]} · Final game ${score[matchWinner]}–${score[loser]}`
            : `Final score ${score[matchWinner]}–${score[loser]}`;
        releaseBalloons();
        celebration.hidden = false;
        requestAnimationFrame(() => celebration.classList.add('is-visible'));
        scoringElement('#rematch').focus();
    }

    function hideCelebration() {
        if (!celebration || celebration.hidden) return;
        celebration.classList.remove('is-visible');
        celebration.hidden = true;
        celebration.querySelector('.balloons').innerHTML = '';
    }

    // Rematch: same teams and settings; the other side serves first this time.
    scoringElement('#rematch')?.addEventListener('click', () => startScoring(startingTeam === 'A' ? 'B' : 'A'));

    // New match: back to the setup form to change teams or format.
    scoringElement('#new-match')?.addEventListener('click', async () => {
        hideCelebration();
        await exitFullscreen();
        const setup = document.querySelector('.scoring-settings');
        setup?.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
        scoringElement('#team-a-name')?.select();
    });

    scoringElement('#celebration-undo')?.addEventListener('click', () => {
        scoringState = scoringReducer(scoringState, { type: 'UNDO' });
        hideCelebration();
        renderScoreboard();
    });

    /*
    |----------------------------------------------------------------------
    | Controls
    |----------------------------------------------------------------------
    */

    scoringElement('#start-scoring')?.addEventListener('click', () => startScoring('A'));
    document.querySelectorAll('[data-score-team]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!scoringState) startScoring();
            const before = scoringState;
            scoringState = scoringReducer(scoringState, { type: 'SCORE', team: button.dataset.scoreTeam });
            renderScoreboard();

            if (scoringState.matchWinner && !before.matchWinner) {
                showCelebration();
            } else if (scoringState.gameNumber > before.gameNumber) {
                showGameFlash(button.dataset.scoreTeam, before.gameNumber);
            }
        });
    });
    scoringElement('#undo-score')?.addEventListener('click', () => {
        if (!scoringState) return;
        scoringState = scoringReducer(scoringState, { type: 'UNDO' });
        hideCelebration();
        renderScoreboard();
    });
    startScoring();
}
