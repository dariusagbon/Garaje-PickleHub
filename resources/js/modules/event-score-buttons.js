// +/- buttons next to the score inputs on an event page.

document.querySelectorAll('[data-score-target]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.querySelector(`#${button.dataset.scoreTarget}`);
        const nextValue = Math.max(0, Number(input.value || 0) + Number(button.dataset.scoreChange));
        input.value = nextValue;
    });
});
