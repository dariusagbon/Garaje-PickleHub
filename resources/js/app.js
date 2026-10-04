import './bootstrap';
import {
    createInitialState,
    scoringReducer,
} from './scoring-reducer.js';

const menuToggle = document.querySelector('#menu-toggle');
const mainMenu = document.querySelector('#main-menu');
menuToggle?.addEventListener('click', () => {
    const open = menuToggle.getAttribute('aria-expanded') === 'true';
    menuToggle.setAttribute('aria-expanded', String(!open));
    mainMenu?.classList.toggle('hidden', open);
});
mainMenu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 768) {
            mainMenu.classList.add('hidden');
            menuToggle?.setAttribute('aria-expanded', 'false');
        }
    });
});

const calendarGrid = document.querySelector('#calendar-grid');
const scheduleGrid = document.querySelector('#schedule-grid');
const calendarMonth = document.querySelector('#calendar-month');
const selectedDateLabel = document.querySelector('#selected-date');
const availabilitySummary = document.querySelector('#availability-summary');
const reviewBooking = document.querySelector('#review-booking');
const bookingModal = document.querySelector('#booking-modal');
const closeBookingModal = document.querySelector('#close-booking-modal');
const bookingFormElement = document.querySelector('#guest-booking-form');
const bookingError = document.querySelector('#booking-error');
const bookingSubmit = document.querySelector('#booking-submit');
const bookingBar = document.querySelector('#booking-bar');
const courts = ['PickleHub Court'];
const hours = Array.from({ length: 17 }, (_, index) => index + 7);
const periods = [
    { label: 'Morning', icon: '☀', from: 7, to: 11 },
    { label: 'Afternoon', icon: '◐', from: 12, to: 17 },
    { label: 'Evening', icon: '☾', from: 18, to: 23 },
];
const today = new Date(new Date().getFullYear(), new Date().getMonth(), new Date().getDate());
let visibleMonth = new Date(today.getFullYear(), today.getMonth(), 1);
let selectedDate = new Date(today);
let booked = new Set();
let selectedHours = [];
let lastClickedHour = null;
let loaded = false;

const pad = (value) => String(value).padStart(2, '0');
const dateKey = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
const hourText = (hour) => `${hour % 12 || 12}:00 ${hour >= 12 && hour < 24 ? 'PM' : 'AM'}`;
const isBooked = (date, hour) => booked.has(`${dateKey(date)}|${courts[0]}|${hour}`);
const isPastDay = (date) => date < today;
const isPastHour = (date, hour) => {
    if (isPastDay(date)) return true;
    if (dateKey(date) !== dateKey(new Date())) return false;
    return hour <= new Date().getHours();
};
const isOpen = (date, hour) => !isBooked(date, hour) && !isPastHour(date, hour);

// Collapse [9, 10, 11, 15] into "9:00 AM – 12:00 PM, 3:00 PM – 4:00 PM".
function hourRanges(list) {
    const ranges = [];
    list.forEach((hour) => {
        const last = ranges[ranges.length - 1];
        if (last && last[1] === hour) last[1] = hour + 1;
        else ranges.push([hour, hour + 1]);
    });
    return ranges.map(([start, end]) => `${hourText(start)} – ${hourText(end)}`).join(', ');
}

function showToast(message, type = 'success') {
    let stack = document.querySelector('#toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'toast-stack';
        stack.className = 'toast-stack';
        stack.setAttribute('aria-live', 'polite');
        document.body.append(stack);
    }
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    stack.append(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

async function loadBookings() {
    try {
        const response = await fetch('/api/bookings', { headers: { Accept: 'application/json' } });
        const rows = await response.json();
        booked = new Set(rows.map((row) => `${row.booking_date}|${row.court}|${row.hour}`));
    } catch {
        showToast('Could not load live availability. Please refresh.', 'error');
    }
    loaded = true;
    renderCalendar();
    renderSchedule();
}

function renderCalendar() {
    if (!calendarGrid) return;
    const year = visibleMonth.getFullYear();
    const month = visibleMonth.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    calendarMonth.textContent = visibleMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    document.querySelector('#previous-month')?.toggleAttribute('disabled', year === today.getFullYear() && month === today.getMonth());
    calendarGrid.innerHTML = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
        .map((day) => `<div class="calendar-weekday">${day}</div>`).join('');
    calendarGrid.insertAdjacentHTML('beforeend', '<div class="calendar-day empty"></div>'.repeat(firstDay));

    for (let day = 1; day <= daysInMonth; day += 1) {
        const date = new Date(year, month, day);
        const past = isPastDay(date);
        const open = hours.filter((hour) => isOpen(date, hour)).length;
        const bookedCount = hours.filter((hour) => isBooked(date, hour)).length;
        const level = past ? '' : open === 0 ? 'full' : bookedCount / hours.length > 0.5 ? 'busy' : 'open';
        const classes = ['calendar-day', level && `level-${level}`,
            past && 'disabled',
            dateKey(date) === dateKey(selectedDate) && 'selected',
            dateKey(date) === dateKey(today) && 'today'].filter(Boolean).join(' ');
        const status = !loaded ? '<span class="calendar-day-skeleton"></span>'
            : past ? '' : open ? `${open} open` : 'Full';
        calendarGrid.insertAdjacentHTML('beforeend', `
            <button class="${classes}" type="button" data-date="${dateKey(date)}" ${past ? 'disabled' : ''}
                aria-label="${date.toDateString()}${past ? '' : `, ${open} open slots`}" style="--fill:${Math.round((bookedCount / hours.length) * 100)}%">
                <span class="calendar-day-number">${day}</span>
                <span class="calendar-day-status${open ? '' : ' full'}">${status}</span>
                ${past || !loaded ? '' : '<span class="calendar-day-meter"><i></i></span>'}
            </button>`);
    }

    calendarGrid.querySelectorAll('button[data-date]:not([disabled])').forEach((button) => {
        button.addEventListener('click', () => {
            const [y, m, d] = button.dataset.date.split('-').map(Number);
            selectedDate = new Date(y, m - 1, d);
            selectedHours = [];
            lastClickedHour = null;
            renderCalendar();
            renderSchedule(true);
        });
    });
}

function slotButton(hour) {
    const bookedSlot = isBooked(selectedDate, hour);
    const past = !bookedSlot && isPastHour(selectedDate, hour);
    const selected = selectedHours.includes(hour);
    const state = bookedSlot ? 'booked' : past ? 'past' : selected ? 'selected' : 'open';
    const label = { booked: 'Booked', past: 'Passed', selected: 'Selected', open: 'Available' }[state];
    return `<button type="button" class="slot-chip is-${state}" data-hour="${hour}" ${state === 'booked' || state === 'past' ? 'disabled' : ''}
        aria-pressed="${selected}" aria-label="${hourText(hour)} ${label}">
        <span class="slot-chip-time">${hourText(hour)}</span>
        <span class="slot-chip-state">${selected ? '✓ ' : ''}${label}</span>
    </button>`;
}

function renderSchedule(animate = false) {
    if (!scheduleGrid) return;
    const available = hours.filter((hour) => isOpen(selectedDate, hour)).length;
    selectedDateLabel.textContent = selectedDate.toLocaleDateString('en-US', {
        weekday: 'long', month: 'long', day: 'numeric',
    });
    availabilitySummary.textContent = loaded ? `${available} of ${hours.length} hours open` : 'Loading availability…';

    if (!loaded) {
        scheduleGrid.innerHTML = `<div class="slot-period-grid">${'<div class="slot-skeleton"></div>'.repeat(8)}</div>`;
        return;
    }

    scheduleGrid.innerHTML = periods.map((period) => {
        const periodHours = hours.filter((hour) => hour >= period.from && hour <= period.to);
        const open = periodHours.filter((hour) => isOpen(selectedDate, hour)).length;
        return `<div class="slot-period">
            <div class="slot-period-header"><span><i aria-hidden="true">${period.icon}</i> ${period.label}</span><small>${open} open</small></div>
            <div class="slot-period-grid">${periodHours.map(slotButton).join('')}</div>
        </div>`;
    }).join('') + (available ? '' : '<p class="slot-empty">No open hours on this day — try another date.</p>');
    if (animate) {
        scheduleGrid.classList.remove('fade-in');
        void scheduleGrid.offsetWidth;
        scheduleGrid.classList.add('fade-in');
    }

    scheduleGrid.querySelectorAll('.slot-chip:not([disabled])').forEach((slot) => {
        slot.addEventListener('click', (event) => {
            const hour = Number(slot.dataset.hour);
            if (event.shiftKey && lastClickedHour !== null) {
                // Shift-click selects every open hour between the two clicks.
                const [from, to] = [Math.min(lastClickedHour, hour), Math.max(lastClickedHour, hour)];
                const range = hours.filter((h) => h >= from && h <= to && isOpen(selectedDate, h));
                selectedHours = [...new Set([...selectedHours, ...range])].sort((a, b) => a - b);
            } else {
                selectedHours = selectedHours.includes(hour)
                    ? selectedHours.filter((selectedHour) => selectedHour !== hour)
                    : [...selectedHours, hour].sort((a, b) => a - b);
            }
            lastClickedHour = hour;
            renderSchedule();
            scheduleGrid.querySelector(`[data-hour="${hour}"]`)?.focus();
        });
    });
    renderBookingBar();
}

function renderBookingBar() {
    if (reviewBooking) reviewBooking.disabled = selectedHours.length === 0;
    if (!bookingBar) return;
    const visible = selectedHours.length > 0;
    bookingBar.classList.toggle('show', visible);
    bookingBar.setAttribute('aria-hidden', String(!visible));
    if (!visible) return;
    bookingBar.querySelector('[data-bar-count]').textContent = `${selectedHours.length} hour${selectedHours.length > 1 ? 's' : ''}`;
    bookingBar.querySelector('[data-bar-detail]').textContent = `${selectedDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })} · ${hourRanges(selectedHours)}`;
}

function updateModalSummary() {
    const summary = document.querySelector('#booking-hours-summary');
    if (!summary) return;
    summary.innerHTML = `
        <span><small>Date</small>${selectedDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })}</span>
        <span><small>Time</small>${hourRanges(selectedHours)}</span>
        <span><small>Duration</small>${selectedHours.length} hour${selectedHours.length > 1 ? 's' : ''} · ${courts[0]}</span>`;
}

function openBookingModal() {
    if (!selectedHours.length) return;
    updateModalSummary();
    bookingError?.classList.add('hidden');
    bookingModal?.classList.remove('hidden');
    bookingModal?.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(() => bookingModal?.classList.add('open'));
    document.body.classList.add('overflow-hidden');
    document.querySelector('#guest-name')?.focus();
}

function hideBookingModal() {
    bookingModal?.classList.remove('open');
    bookingModal?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    setTimeout(() => bookingModal?.classList.add('hidden'), 200);
}

function changeMonth(offset) {
    visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + offset, 1);
    selectedDate = visibleMonth < today ? new Date(today) : new Date(visibleMonth);
    selectedHours = [];
    lastClickedHour = null;
    calendarGrid?.classList.remove('slide-left', 'slide-right');
    void calendarGrid?.offsetWidth;
    calendarGrid?.classList.add(offset > 0 ? 'slide-left' : 'slide-right');
    renderCalendar();
    renderSchedule(true);
}

reviewBooking?.addEventListener('click', openBookingModal);
bookingBar?.querySelector('[data-bar-review]')?.addEventListener('click', openBookingModal);
bookingBar?.querySelector('[data-bar-clear]')?.addEventListener('click', () => {
    selectedHours = [];
    renderSchedule();
});
closeBookingModal?.addEventListener('click', hideBookingModal);
bookingModal?.addEventListener('click', (event) => {
    if (event.target === bookingModal) hideBookingModal();
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && bookingModal?.classList.contains('open')) hideBookingModal();
});
bookingFormElement?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(bookingFormElement));
    bookingError?.classList.add('hidden');
    bookingSubmit?.classList.add('loading');
    if (bookingSubmit) bookingSubmit.disabled = true;
    try {
        const response = await fetch('/bookings', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                ...data,
                booking_date: dateKey(selectedDate),
                court: courts[0],
                hours: selectedHours,
            }),
        });
        const result = await response.json();
        if (!response.ok) {
            const firstError = result.errors && Object.values(result.errors)[0]?.[0];
            if (bookingError) {
                bookingError.textContent = firstError || result.message || 'Unable to book these hours.';
                bookingError.classList.remove('hidden');
            }
            if (response.status === 409) await loadBookings();
            return;
        }
        const summary = `${selectedDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })} · ${hourRanges(selectedHours)}`;
        hideBookingModal();
        selectedHours = [];
        bookingFormElement.reset();
        const confirmation = document.querySelector('#booking-confirmation');
        if (confirmation) {
            confirmation.innerHTML = `<strong>✓ You're booked!</strong><span>${summary}</span><small>See you on the court, ${data.guest_name}.</small>`;
            confirmation.classList.remove('hidden');
        }
        showToast(result.message);
        await loadBookings();
    } catch {
        if (bookingError) {
            bookingError.textContent = 'Network error — please try again.';
            bookingError.classList.remove('hidden');
        }
    } finally {
        bookingSubmit?.classList.remove('loading');
        if (bookingSubmit) bookingSubmit.disabled = false;
    }
});

document.querySelector('#previous-month')?.addEventListener('click', () => changeMonth(-1));
document.querySelector('#next-month')?.addEventListener('click', () => changeMonth(1));

if (calendarGrid) {
    renderCalendar();
    renderSchedule();
    loadBookings();
}

// Reveal sections as they scroll into view.
const revealTargets = document.querySelectorAll('[data-reveal]');
if ('IntersectionObserver' in window && revealTargets.length) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('revealed');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12 });
    revealTargets.forEach((target) => observer.observe(target));
} else {
    revealTargets.forEach((target) => target.classList.add('revealed'));
}

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

document.querySelectorAll('[data-score-target]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.querySelector(`#${button.dataset.scoreTarget}`);
        const nextValue = Math.max(0, Number(input.value || 0) + Number(button.dataset.scoreChange));
        input.value = nextValue;
    });
});

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
