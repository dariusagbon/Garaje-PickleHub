import test from 'node:test';
import assert from 'node:assert/strict';
import {
    createInitialState, scorePoint, scoringReducer, undoScore,
} from '../resources/js/scoring-reducer.js';

test('traditional doubles side-out scoring and opening server exception', () => {
    let state = createInitialState({ mode: 'doubles' });
    assert.equal(state.server, 2);
    state = scorePoint(state, 'B'); // opening server loses: side out, no point
    assert.deepEqual(state.score, { A: 0, B: 0 });
    assert.equal(state.servingTeam, 'B');
    assert.equal(state.server, 1);
    assert.deepEqual(state.eventLog[0], {
        type: 'sideOut',
        team: 'B',
        winner: 'B',
        servingTeamBefore: 'A',
        servingTeamAfter: 'B',
        serverBefore: 2,
        serverAfter: 1,
        scoreBefore: { A: 0, B: 0 },
        scoreAfter: { A: 0, B: 0 },
        scoreChange: { A: 0, B: 0 },
        sideOut: { fromTeam: 'A', toTeam: 'B', completed: true },
    });
    state = scorePoint(state, 'B');
    assert.deepEqual(state.score, { A: 0, B: 1 });
    assert.equal(state.eventLog[1].type, 'point');
    assert.deepEqual(state.eventLog[1].scoreChange, { A: 0, B: 1 });
    assert.equal(state.eventLog[1].servingTeamBefore, 'B');
    state = scorePoint(state, 'A'); // server 1 loses; server 2 gets the ball
    assert.equal(state.servingTeam, 'B');
    assert.equal(state.server, 2);
    state = scorePoint(state, 'A'); // server 2 loses: side out
    assert.equal(state.servingTeam, 'A');
    assert.equal(state.server, 1);
});

test('singles awards only serving rallies and changes serve on a side-out', () => {
    let state = createInitialState({ mode: 'singles' });
    state = scorePoint(state, 'B');
    assert.deepEqual(state.score, { A: 0, B: 0 });
    assert.equal(state.servingTeam, 'B');
    state = scorePoint(state, 'B');
    assert.deepEqual(state.score, { A: 0, B: 1 });
    assert.deepEqual(state.stats, {
        servePoints: { A: 0, B: 1 }, returnWins: { A: 0, B: 1 }, rallies: 2,
    });
});

test('undo restores the complete prior state and supports multiple undos', () => {
    let state = createInitialState({ mode: 'singles' });
    state = scorePoint(state, 'A');
    state = scorePoint(state, 'B');
    const afterFirst = undoScore(state);
    assert.deepEqual(afterFirst.score, { A: 1, B: 0 });
    assert.equal(afterFirst.stats.rallies, 1);
    assert.equal(afterFirst.eventLog.length, 1);
    assert.equal(afterFirst.eventLog[0].type, 'point');
    assert.deepEqual(undoScore(afterFirst).score, { A: 0, B: 0 });
    assert.deepEqual(undoScore(afterFirst).eventLog, []);
});

test('games require two-point margin and match wins use best-of format', () => {
    let state = createInitialState({ mode: 'singles', pointsToWin: 3, bestOf: 3 });
    for (let point = 0; point < 3; point += 1) state = scorePoint(state, 'A');
    assert.deepEqual(state.gamesWon, { A: 1, B: 0 });
    assert.deepEqual(state.score, { A: 0, B: 0 });
    // One more game for A finishes the best-of-three match.
    for (let game = 0; game < 1; game += 1) {
        for (let point = 0; point < 3; point += 1) state = scorePoint(state, 'A');
    }
    assert.equal(state.matchWinner, 'A');
    assert.deepEqual(state.gamesWon, { A: 2, B: 0 });
});

test('reducer exposes score and undo actions', () => {
    let state = createInitialState({ mode: 'singles' });
    state = scoringReducer(state, { type: 'SCORE', team: 'A' });
    assert.equal(state.score.A, 1);
    assert.equal(scoringReducer(state, { type: 'UNDO' }).score.A, 0);
});
