// Live scoring on an event page: the +/- buttons and typed scores save
// automatically. Each game card is a form marked with data-live-score.

const SAVE_DELAY_MS = 500; // wait for a pause in tapping before saving
const RETRY_DELAY_MS = 3000;

function setupLiveScore(form) {
    const inputs = form.querySelectorAll('input[name="score_a"], input[name="score_b"]');
    const statusBox = form.querySelector('[data-save-status]');
    const statusText = form.querySelector('[data-save-text]');
    const card = form.closest('.scoreboard-card');
    const result = card?.querySelector('[data-game-result]');
    const gameState = card?.querySelector('[data-game-state]');
    const token = form.querySelector('input[name="_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content
        || '';

    let timer = null;
    let latestRequest = 0;
    let unsaved = false;

    const setStatus = (state, text) => {
        statusBox.dataset.state = state;
        statusText.textContent = text;
    };

    const scores = () => ({
        score_a: Math.max(0, Number(form.score_a.value || 0)),
        score_b: Math.max(0, Number(form.score_b.value || 0)),
    });

    async function save() {
        const requestId = ++latestRequest;
        setStatus('saving', 'Saving…');

        try {
            const response = await fetch(form.action, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify(scores()),
            });

            // A newer tap is already being saved; ignore this older reply.
            if (requestId !== latestRequest) return;

            const data = await response.json().catch(() => ({}));

            if (response.status === 422) {
                unsaved = false;
                setStatus('error', data.message || 'That score could not be saved.');
                return;
            }
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            unsaved = false;
            const time = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            setStatus('saved', `Saved ✓ ${time}`);

            if (result) {
                result.textContent = data.winner || 'In progress';
                result.classList.toggle('complete', Boolean(data.complete));
            }
            if (gameState) gameState.textContent = data.complete ? 'Final' : 'Live';
            card?.classList.toggle('is-final', Boolean(data.complete));

            if (data.next_game_ready) {
                setStatus('saved', 'Game over! Loading the next game…');
                setTimeout(() => window.location.reload(), 1200);
            }
        } catch {
            if (requestId !== latestRequest) return;
            setStatus('error', 'Couldn’t save. Retrying…');
            timer = setTimeout(save, RETRY_DELAY_MS);
        }
    }

    const scheduleSave = () => {
        unsaved = true;
        setStatus('pending', 'Saving…');
        clearTimeout(timer);
        timer = setTimeout(save, SAVE_DELAY_MS);
    };

    inputs.forEach((input) => input.addEventListener('input', scheduleSave));

    form.querySelectorAll('[data-score-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.querySelector(`#${button.dataset.scoreTarget}`);
            input.value = Math.max(0, Number(input.value || 0) + Number(button.dataset.scoreChange));
            scheduleSave();
        });
    });

    // Enter in a score box saves straight away instead of reloading the page.
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearTimeout(timer);
        save();
    });

    window.addEventListener('beforeunload', (event) => {
        if (unsaved) event.preventDefault();
    });
}

document.querySelectorAll('form[data-live-score]').forEach(setupLiveScore);
