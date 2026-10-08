// Court booking on the home page: month calendar, hour picker,
// review dialog and the sticky selection bar.
// Only runs when the page has a #calendar-grid.

import { showToast } from './toast.js';

const calendarGrid = document.querySelector('#calendar-grid');
const scheduleGrid = document.querySelector('#schedule-grid');
const calendarMonth = document.querySelector('#calendar-month');
const selectedDateLabel = document.querySelector('#selected-date');
const availabilitySummary = document.querySelector('#availability-summary');
const reviewBooking = document.querySelector('#review-booking');
const schedulePanel = document.querySelector('.schedule-panel');
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
const openHours = (date) => hours.filter((hour) => isOpen(date, hour)).length;
// A day with no open hours left can't be picked (only known once bookings have loaded).
const isFullDay = (date) => loaded && !isPastDay(date) && openHours(date) === 0;
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// If the selected day is fully booked, move to the next day in the month that still has open hours.
function skipFullDay() {
    if (!isFullDay(selectedDate)) return;
    const year = selectedDate.getFullYear();
    const month = selectedDate.getMonth();
    const last = new Date(year, month + 1, 0).getDate();
    for (let day = selectedDate.getDate() + 1; day <= last; day += 1) {
        const date = new Date(year, month, day);
        if (!isFullDay(date)) {
            selectedDate = date;
            return;
        }
    }
}

// On phones the hours are listed below the calendar: bring them into view after a day is picked.
function revealSchedule() {
    if (!schedulePanel || !calendarGrid) return;
    const stacked = schedulePanel.getBoundingClientRect().top >= calendarGrid.getBoundingClientRect().bottom;
    if (stacked) schedulePanel.scrollIntoView({ behavior: reducedMotion() ? 'auto' : 'smooth', block: 'start' });
}

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


async function loadBookings() {
    try {
        const response = await fetch('/api/bookings', { headers: { Accept: 'application/json' } });
        const rows = await response.json();
        booked = new Set(rows.map((row) => `${row.booking_date}|${row.court}|${row.hour}`));
    } catch {
        showToast('Could not load live availability. Please refresh.', 'error');
    }
    loaded = true;
    skipFullDay();
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
        const full = isFullDay(date);
        const open = openHours(date);
        const bookedCount = hours.filter((hour) => isBooked(date, hour)).length;
        const level = past ? '' : open === 0 ? 'full' : bookedCount / hours.length > 0.5 ? 'busy' : 'open';
        const classes = ['calendar-day', level && `level-${level}`,
            past && 'disabled',
            full && 'is-full',
            dateKey(date) === dateKey(selectedDate) && 'selected',
            dateKey(date) === dateKey(today) && 'today'].filter(Boolean).join(' ');
        const status = !loaded ? '<span class="calendar-day-skeleton"></span>'
            : past ? '' : open ? `${open} open` : 'Full';
        calendarGrid.insertAdjacentHTML('beforeend', `
            <button class="${classes}" type="button" data-date="${dateKey(date)}" ${past || full ? 'disabled' : ''}
                aria-label="${date.toDateString()}${past ? '' : full ? ', fully booked' : `, ${open} open slots`}" style="--fill:${Math.round((bookedCount / hours.length) * 100)}%">
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
            revealSchedule();
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
    const shortDate = selectedDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    bookingBar.querySelector('[data-bar-detail]').textContent = `${shortDate} · ${hourRanges(selectedHours)}`;
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
    skipFullDay();
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
        const longDate = selectedDate.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
        const summary = `${longDate} · ${hourRanges(selectedHours)}`;
        hideBookingModal();
        selectedHours = [];
        bookingFormElement.reset();
        const confirmation = document.querySelector('#booking-confirmation');
        if (confirmation) {
            confirmation.innerHTML = `
                <strong>✓ You're booked!</strong>
                <span>${summary}</span>
                <small>See you on the court, ${data.guest_name}.</small>`;
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
