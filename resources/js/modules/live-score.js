// Live rally-by-rally scoring on an event page (side-out rules are applied on
// the server, see app/Services/SideOutScoring.php). Each game card is a form
// marked data-live-score; its buttons send an "action": rally_a, rally_b,
// undo, serve_a or serve_b. Taps are queued and sent in order.

const RETRY_DELAY_MS = 3000;

function setupLiveScore(form) {
    const card = form.closest('.scoreboard-card');
    const el = (selector) => form.querySelector(selector);
    const statusBox = el('[data-save-status]');
    const statusText = el('[data-save-text]');
    const ralliesSeen = el('[data-rallies-seen]');
    // Read the URL from the attribute: form.action would return the buttons named "action".
    const url = form.getAttribute('action');
    const token = form.querySelector('input[name="_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content
        || '';

    let queue = Promise.resolve();
    let pending = 0;

    const setStatus = (state, text) => {
        statusBox.dataset.state = state;
        statusText.textContent = text;
    };

    // Paint the card from the state the server sent back.
    function render(state) {
        ralliesSeen.value = state.rallies;

        form.querySelectorAll('[data-team]').forEach((panel) => {
            const team = panel.dataset.team;
            const serving = !state.complete && state.serving_team === team;
            panel.querySelector('[data-score]').textContent = state[`score_${team.toLowerCase()}`];
            panel.classList.toggle('is-serving', serving);
            panel.querySelector('[data-serve-badge]').hidden = !serving;
            panel.querySelector('[data-server]').textContent = state.server;
            panel.querySelector('[data-rally]').disabled = state.complete;
        });

        el('[data-call-bar]').hidden = state.complete;
        el('[data-call]').textContent = state.call;
        el('[data-serve-info]').textContent =
            `Team ${state.serving_team} serving · Server ${state.server} · serve from the ${state.serve_from}`;

        el('[data-undo]').disabled = !state.can_undo;
        el('[data-first-serve]').hidden = state.rallies > 0 || state.complete || state.score_a > 0 || state.score_b > 0;
        form.querySelectorAll('[data-first-serve-option]').forEach((option) => {
            option.classList.toggle('is-selected', option.dataset.firstServeOption === state.serving_team);
        });

        const result = card?.querySelector('[data-game-result]');
        if (result) {
            result.textContent = state.winner_label || 'In progress';
            result.classList.toggle('complete', state.complete);
        }
        const gameState = card?.querySelector('[data-game-state]');
        if (gameState) gameState.textContent = state.complete ? 'Final' : 'Live';
        card?.classList.toggle('is-final', state.complete);
    }

    async function send(action) {
        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ action, rallies_seen: Number(ralliesSeen.value) }),
            });
            const data = await response.json().catch(() => null);

            if (!data || response.status >= 500) throw new Error(`HTTP ${response.status}`);

            if ('call' in data) render(data);

            if (!response.ok) {
                setStatus('error', data.message || 'That could not be saved.');
                return;
            }

            const time = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            setStatus('saved', data.complete ? `Game over · saved ${time}` : `Saved ✓ ${time}`);

            if (data.next_game_ready) {
                setStatus('saved', 'Game over! Loading the next game…');
                setTimeout(() => window.location.reload(), 1200);
            }
        } catch {
            setStatus('error', 'Couldn’t save. Retrying…');
            await new Promise((resolve) => setTimeout(resolve, RETRY_DELAY_MS));
            return send(action);
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const action = event.submitter?.value;
        if (!action) return;

        pending += 1;
        setStatus('saving', 'Saving…');
        queue = queue.then(() => send(action)).finally(() => {
            pending -= 1;
        });
    });

    window.addEventListener('beforeunload', (event) => {
        if (pending > 0) event.preventDefault();
    });
}

document.querySelectorAll('form[data-live-score]').forEach(setupLiveScore);
