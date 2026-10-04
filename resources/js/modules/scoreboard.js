// Open scoreboard page (/scoring), driven by scoring-reducer.js.

import {
    createInitialState,
    scoringReducer,
} from '../scoring-reducer.js';

const scoringBoard = document.querySelector('#scoreboard');
if (scoringBoard) {
    let scoringState = null;
    let teamNames = { A: 'Team A', B: 'Team B' };

    const scoringElement = (selector) => document.querySelector(selector);
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

    function startScoring() {
        teamNames = {
            A: scoringElement('#team-a-name').value.trim() || 'Team A',
            B: scoringElement('#team-b-name').value.trim() || 'Team B',
        };
        scoringState = createInitialState({
            mode: scoringElement('#scoring-mode').value,
            pointsToWin: Number(scoringElement('#points-to-win').value),
            bestOf: Number(scoringElement('#match-format').value),
            startingTeam: 'A',
        });
        renderScoreboard();
    }

    scoringElement('#start-scoring')?.addEventListener('click', startScoring);
    document.querySelectorAll('[data-score-team]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!scoringState) startScoring();
            scoringState = scoringReducer(scoringState, { type: 'SCORE', team: button.dataset.scoreTeam });
            renderScoreboard();
        });
    });
    scoringElement('#undo-score')?.addEventListener('click', () => {
        if (!scoringState) return;
        scoringState = scoringReducer(scoringState, { type: 'UNDO' });
        renderScoreboard();
    });
    startScoring();
}
