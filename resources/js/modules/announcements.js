// Lets a visitor hide an announcement on their own device (remembered in
// localStorage). It still expires for everyone after 24 hours on the server.

const STORAGE_KEY = 'picklehub.hiddenAnnouncements';

function readHidden() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    } catch {
        return [];
    }
}

function remember(id) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify([...new Set([...readHidden(), id])].slice(-50)));
    } catch {
        // Storage unavailable (private mode): the announcement is just hidden for this page view.
    }
}

const hidden = readHidden();

document.querySelectorAll('[data-announcement]').forEach((item) => {
    const id = item.dataset.announcement;
    if (hidden.includes(id)) item.remove();

    item.querySelector('[data-dismiss-announcement]')?.addEventListener('click', () => {
        remember(id);
        item.classList.add('is-leaving');
        setTimeout(() => item.remove(), 200);
    });
});

// Remove the empty banner strip once nothing is left in it.
document.querySelectorAll('.announcements').forEach((section) => {
    const update = () => {
        if (!section.querySelector('[data-announcement]')) section.remove();
    };
    update();
    new MutationObserver(update).observe(section, { childList: true });
});
