// Small playful touches used across the site: tap ripples, confetti for
// good news, and dashboard numbers that count up when they come into view.
// Nothing here runs for people who prefer reduced motion.

const calm = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/*
|--------------------------------------------------------------------------
| Confetti
|--------------------------------------------------------------------------
*/

const COLOURS = ['#c25546', '#e8968b', '#3f855a', '#bfdcb2', '#173d2a', '#eee4d1'];

export function confetti(pieces = 90) {
    if (calm()) return;
    const layer = document.createElement('div');
    layer.className = 'confetti-layer';
    layer.setAttribute('aria-hidden', 'true');
    for (let i = 0; i < pieces; i += 1) {
        const piece = document.createElement('span');
        piece.className = `confetti-piece${i % 6 === 0 ? ' is-ball' : ''}`;
        piece.style.left = `${Math.random() * 100}%`;
        piece.style.setProperty('--colour', COLOURS[i % COLOURS.length]);
        piece.style.setProperty('--drift', `${(Math.random() - 0.5) * 240}px`);
        piece.style.setProperty('--spin', `${(Math.random() - 0.5) * 1440}deg`);
        piece.style.setProperty('--delay', `${Math.random() * 0.35}s`);
        piece.style.setProperty('--duration', `${1.6 + Math.random() * 1.4}s`);
        layer.appendChild(piece);
    }
    document.body.appendChild(layer);
    setTimeout(() => layer.remove(), 3600);
}

// Celebrate success messages that arrive with a page load (e.g. joining an event).
if (document.querySelector('.ev-alert-success')) {
    window.addEventListener('load', () => confetti(), { once: true });
}

/*
|--------------------------------------------------------------------------
| Tap ripple
|--------------------------------------------------------------------------
*/

const RIPPLE_TARGETS = '.button, .calendar-day, .slot-chip, .score-point-button, .rally-button, .first-serve-option, .calendar-arrow';

document.addEventListener('pointerdown', (event) => {
    if (calm()) return;
    const target = event.target.closest(RIPPLE_TARGETS);
    if (!target || target.disabled) return;
    const style = getComputedStyle(target);
    if (style.position === 'static') target.style.position = 'relative';
    target.style.overflow = 'hidden';
    const rect = target.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${event.clientY - rect.top - size / 2}px`;
    target.appendChild(ripple);
    ripple.addEventListener('animationend', () => ripple.remove());
});

/*
|--------------------------------------------------------------------------
| Count-up numbers on the dashboards
|--------------------------------------------------------------------------
*/

function countUp(textNode, target) {
    const duration = 900;
    const start = performance.now();
    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - (1 - progress) ** 3;
        textNode.textContent = String(Math.round(target * eased));
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}

const counters = [...document.querySelectorAll('.dash-stat strong:not([data-count-to]), .admin-stat strong:not([data-count-to]), .admin-kpi strong:not([data-count-to])')]
    .map((element) => {
        // Only the leading whole number is animated; any units after it stay as they are.
        const textNode = [...element.childNodes].find((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
        const value = textNode && /^\s*\d+\s*$/.test(textNode.textContent) ? Number(textNode.textContent) : null;
        return value ? { element, textNode, value } : null;
    })
    .filter(Boolean);

if (counters.length && !calm() && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const counter = counters.find((c) => c.element === entry.target);
            observer.unobserve(entry.target);
            if (counter) countUp(counter.textNode, counter.value);
        });
    }, { threshold: 0.4 });
    counters.forEach(({ element, textNode }) => {
        textNode.textContent = textNode.textContent.replace(/\d+/, '0');
        observer.observe(element);
    });
}
