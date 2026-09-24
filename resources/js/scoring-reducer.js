/**
 * Pure traditional side-out pickleball scoring.
 *
 * @typedef {'A'|'B'} Team
 * @typedef {'singles'|'doubles'} Mode
 */

const TEAMS = ['A', 'B'];

const otherTeam = (team) => (team === 'A' ? 'B' : 'A');

const copyState = (state) => ({
    ...state,
    score: { ...state.score },
    gamesWon: { ...state.gamesWon },
    stats: {
        servePoints: { ...state.stats.servePoints },
        returnWins: { ...state.stats.returnWins },
        rallies: state.stats.rallies,
    },
    eventLog: state.eventLog.map((event) => ({
        ...event,
        scoreBefore: { ...event.scoreBefore },
        scoreAfter: { ...event.scoreAfter },
        scoreChange: { ...event.scoreChange },
        sideOut: event.sideOut ? { ...event.sideOut } : null,
    })),
    history: state.history.map((snapshot) => copyState({ ...snapshot, history: [] })),
});

/**
 * @param {{ mode?: Mode, pointsToWin?: number, bestOf?: 1|3|5, startingTeam?: Team }} options
 */
export function createInitialState(options = {}) {
    const mode = options.mode || 'doubles';
    const pointsToWin = options.pointsToWin || 11;
    const bestOf = options.bestOf || 1;
    if (!['singles', 'doubles'].includes(mode)) throw new Error('mode must be singles or doubles');
    if (!Number.isInteger(pointsToWin) || pointsToWin < 1) throw new Error('pointsToWin must be a positive integer');
    if (![1, 3, 5].includes(bestOf)) throw new Error('bestOf must be 1, 3, or 5');

    const startingTeam = options.startingTeam || 'A';
    return {
        mode,
        pointsToWin,
        bestOf,
        score: { A: 0, B: 0 },
        gamesWon: { A: 0, B: 0 },
        gameNumber: 1,
        servingTeam: startingTeam,
        // Doubles games begin with the special “server 2” exception.
        server: mode === 'doubles' ? 2 : 1,
        openingServeException: mode === 'doubles',
        matchWinner: null,
        stats: { servePoints: { A: 0, B: 0 }, returnWins: { A: 0, B: 0 }, rallies: 0 },
        eventLog: [],
        history: [],
    };
}

export const checkGameWon = (state, team) =>
    state.score[team] >= state.pointsToWin &&
    state.score[team] - state.score[otherTeam(team)] >= 2;

export const checkMatchWon = (state, team) =>
    state.gamesWon[team] >= Math.floor(state.bestOf / 2) + 1;

const resetForNextGame = (state, winner) => ({
    ...state,
    score: { A: 0, B: 0 },
    gameNumber: state.gameNumber + 1,
    servingTeam: winner,
    server: state.mode === 'doubles' ? 2 : 1,
    openingServeException: state.mode === 'doubles',
});

/**
 * Apply one rally. The input state is never mutated.
 * @param {ReturnType<typeof createInitialState>} state
 * @param {Team} winner
 */
export function scorePoint(state, winner) {
    if (!TEAMS.includes(winner)) throw new Error('winner must be A or B');
    if (state.matchWinner) return state;

    const next = copyState(state);
    const servingTeamBefore = state.servingTeam;
    const serverBefore = state.server;
    const scoreBefore = { ...state.score };
    next.history.push(copyState({ ...state, history: [] }));
    next.stats.rallies += 1;

    if (winner === state.servingTeam) {
        next.score[winner] += 1;
        next.stats.servePoints[winner] += 1;
    } else {
        next.stats.returnWins[winner] += 1;
        if (state.mode === 'singles') {
            next.servingTeam = winner;
        } else if (state.openingServeException) {
            // The opening server is the only server, despite being called 2.
            next.servingTeam = winner;
            next.server = 1;
            next.openingServeException = false;
        } else if (state.server === 1) {
            next.server = 2;
        } else {
            next.servingTeam = winner;
            next.server = 1;
        }
    }

    const scoredPoint = winner === servingTeamBefore;
    const rallyScoreAfter = { ...next.score };
    const scoreChange = {
        A: rallyScoreAfter.A - scoreBefore.A,
        B: rallyScoreAfter.B - scoreBefore.B,
    };
    if (!checkGameWon(next, winner)) {
        next.eventLog.push({
            type: scoredPoint ? 'point' : 'sideOut',
            team: winner,
            winner,
            servingTeamBefore,
            servingTeamAfter: next.servingTeam,
            serverBefore,
            serverAfter: next.server,
            scoreBefore,
            scoreAfter: rallyScoreAfter,
            scoreChange,
            sideOut: scoredPoint ? null : {
                fromTeam: servingTeamBefore,
                toTeam: next.servingTeam,
                completed: servingTeamBefore !== next.servingTeam,
            },
        });
        return next;
    }
    next.gamesWon[winner] += 1;
    if (checkMatchWon(next, winner)) {
        next.matchWinner = winner;
    } else {
        Object.assign(next, resetForNextGame(next, winner));
    }
    next.eventLog.push({
        type: scoredPoint ? 'point' : 'sideOut',
        team: winner,
        winner,
        servingTeamBefore,
        servingTeamAfter: next.servingTeam,
        serverBefore,
        serverAfter: next.server,
        scoreBefore,
        scoreAfter: rallyScoreAfter,
        scoreChange,
        sideOut: scoredPoint ? null : {
            fromTeam: servingTeamBefore,
            toTeam: next.servingTeam,
            completed: servingTeamBefore !== next.servingTeam,
        },
    });
    return next;
}

/** Reducer-compatible interface for score and undo controls. */
export function scoringReducer(state, action) {
    if (action?.type === 'SCORE') return scorePoint(state, action.team);
    if (action?.type === 'UNDO') return undoScore(state);
    return state;
}

export function undoScore(state) {
    if (!state.history.length) return state;
    const previous = state.history[state.history.length - 1];
    const restored = copyState(previous);
    restored.history = state.history.slice(0, -1).map((snapshot) => copyState({ ...snapshot, history: [] }));
    return restored;
}
