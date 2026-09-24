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
const courts = ['PickleHub Court'];
const hours = Array.from({ length: 17 }, (_, index) => index + 7);
const today = new Date();
let visibleMonth = new Date(today.getFullYear(), today.getMonth(), 1);
let selectedDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
let booked = new Set();
let selectedHours = [];

const dateKey = (date) => date.toISOString().slice(0, 10);
const hourText = (hour) => `${hour % 12 || 12}:00 ${hour >= 12 ? 'PM' : 'AM'}`;
const isBooked = (date, hour) => booked.has(`${dateKey(date)}|${courts[0]}|${hour}`);

async function loadBookings() {
    const response = await fetch('/api/bookings');
    const rows = await response.json();
    booked = new Set(rows.filter((row) => row.status !== 'cancelled')
        .map((row) => `${row.booking_date}|${row.court}|${row.hour}`));
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
    calendarGrid.innerHTML = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
        .map((day) => `<div class="calendar-weekday">${day}</div>`).join('');
    calendarGrid.insertAdjacentHTML('beforeend', '<div class="calendar-day empty"></div>'.repeat(firstDay));

    for (let day = 1; day <= daysInMonth; day += 1) {
        const date = new Date(year, month, day);
        const available = hours.filter((hour) => !isBooked(date, hour)).length;
        const selected = date.toDateString() === selectedDate.toDateString();
        calendarGrid.insertAdjacentHTML('beforeend', `
            <button class="calendar-day${selected ? ' selected' : ''}" type="button" data-date="${date.toISOString()}">
                ${day}<span class="calendar-day-status${available ? '' : ' full'}">${available ? `${available} open slots` : 'Fully booked'}</span>
            </button>`);
    }

    calendarGrid.querySelectorAll('button[data-date]').forEach((button) => {
        button.addEventListener('click', () => {
            selectedDate = new Date(button.dataset.date);
            selectedHours = [];
            renderCalendar();
            renderSchedule();
        });
    });
}

function renderSchedule() {
    if (!scheduleGrid) return;
    const available = hours.filter((hour) => !isBooked(selectedDate, hour)).length;
    selectedDateLabel.textContent = selectedDate.toLocaleDateString('en-US', {
        weekday: 'long', month: 'long', day: 'numeric',
    });
    availabilitySummary.textContent = `${available} available slots${selectedHours.length ? ` · ${selectedHours.length} selected` : ''}`;
    if (reviewBooking) reviewBooking.disabled = selectedHours.length === 0;
    scheduleGrid.innerHTML = `
        <div class="schedule-row">
            <div class="schedule-cell schedule-heading">Time</div>
            <div class="schedule-cell schedule-heading">PickleHub Court</div>
        </div>
        ${hours.map((hour) => {
            const bookedSlot = isBooked(selectedDate, hour);
            const selected = selectedHours.includes(hour);
            return `<div class="schedule-row">
                <div class="schedule-cell schedule-time">${hourText(hour)}</div>
                <div class="schedule-cell">
                    <button class="schedule-slot${bookedSlot ? ' booked' : ''}${selected ? ' selected' : ''}" ${bookedSlot ? 'disabled' : ''} data-hour="${hour}">
                        <strong>${bookedSlot ? 'Fully booked' : selected ? 'Selected' : 'Available'}</strong>
                        <span>${bookedSlot ? 'Booked' : selected ? 'Included in booking' : 'Select hour'}</span>
                    </button>
                </div>
            </div>`;
        }).join('')}`;

    scheduleGrid.querySelectorAll('.schedule-slot:not(.booked)').forEach((slot) => {
        slot.addEventListener('click', () => {
            const hour = Number(slot.dataset.hour);
            selectedHours = selectedHours.includes(hour)
                ? selectedHours.filter((selectedHour) => selectedHour !== hour)
                : [...selectedHours, hour].sort((a, b) => a - b);
            renderSchedule();
        });
    });
}

function updateModalSummary() {
    const summary = document.querySelector('#booking-hours-summary');
    if (summary) summary.textContent = `${selectedDate.toLocaleDateString()} · ${selectedHours.map(hourText).join(', ')}`;
}

function openBookingModal() {
    if (!selectedHours.length) return;
    updateModalSummary();
    bookingModal?.classList.remove('hidden');
    bookingModal?.setAttribute('aria-hidden', 'false');
    document.querySelector('#guest-name')?.focus();
}

function hideBookingModal() {
    bookingModal?.classList.add('hidden');
    bookingModal?.setAttribute('aria-hidden', 'true');
}

reviewBooking?.addEventListener('click', openBookingModal);
closeBookingModal?.addEventListener('click', hideBookingModal);
bookingModal?.addEventListener('click', (event) => {
    if (event.target === bookingModal) hideBookingModal();
});
bookingFormElement?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(bookingFormElement));
    const response = await fetch('/bookings', {
        method: 'POST',
        headers: {
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
        alert(result.message || 'Unable to book these hours.');
        return;
    }
    hideBookingModal();
    selectedHours = [];
    bookingFormElement.reset();
    const confirmation = document.querySelector('#booking-confirmation');
    if (confirmation) {
        confirmation.textContent = result.message;
        confirmation.classList.remove('hidden');
    }
    await loadBookings();
});

document.querySelector('#previous-month')?.addEventListener('click', () => {
    visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
    selectedDate = new Date(visibleMonth);
    selectedHours = [];
    renderCalendar();
    renderSchedule();
});
document.querySelector('#next-month')?.addEventListener('click', () => {
    visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
    selectedDate = new Date(visibleMonth);
    selectedHours = [];
    renderCalendar();
    renderSchedule();
});

if (calendarGrid) loadBookings();

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
