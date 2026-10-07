document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    const password = document.getElementById(toggle.getAttribute('aria-controls'));
    if (!(password instanceof HTMLInputElement)) return;

    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const showPassword = password.type === 'password';
        password.type = showPassword ? 'text' : 'password';
        toggle.textContent = showPassword ? 'Hide' : 'Show';
        toggle.setAttribute('aria-pressed', String(showPassword));
    });
});
