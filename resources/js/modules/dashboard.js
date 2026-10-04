// Player and admin dashboards: greeting, event countdown and count-up stats.

const greeting = document.querySelector('[data-greeting]');
if (greeting) {
    const hour = new Date().getHours();
    greeting.textContent = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
}

const countdown = document.querySelector('[data-countdown]');
if (countdown) {
    const target = new Date(countdown.dataset.countdown);
    const tick = () => {
        const diff = target - new Date();
        if (diff <= 0) {
            countdown.classList.add('hidden');
            document.querySelector('.dash-countdown-live')?.classList.remove('hidden');
            return;
        }
        const parts = {
            days: Math.floor(diff / 86400000),
            hours: Math.floor(diff / 3600000) % 24,
            minutes: Math.floor(diff / 60000) % 60,
            seconds: Math.floor(diff / 1000) % 60,
        };
        Object.entries(parts).forEach(([unit, value]) => {
            countdown.querySelector(`[data-unit="${unit}"]`).textContent = String(value).padStart(2, '0');
        });
        setTimeout(tick, 1000);
    };
    tick();
}

document.querySelectorAll('[data-count-to]').forEach((element) => {
    const end = Number(element.dataset.countTo);
    if (!end || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const start = performance.now();
    const step = (now) => {
        const progress = Math.min(1, (now - start) / 800);
        element.textContent = Math.round(end * (1 - (1 - progress) ** 3));
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
});
