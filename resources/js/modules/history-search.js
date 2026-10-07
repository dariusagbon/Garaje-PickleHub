// Search box on the admin Event history page.

const historySearch = document.querySelector('#history-search');
historySearch?.addEventListener('input', () => {
    const term = historySearch.value.trim().toLowerCase();
    let shown = 0;
    document.querySelectorAll('[data-history-search]').forEach((item) => {
        const match = item.dataset.historySearch.includes(term);
        item.classList.toggle('hidden', !match);
        if (match) shown += 1;
        if (term && match) item.open = true;
    });
    document.querySelector('#history-no-results')?.classList.toggle('hidden', shown > 0);
});
