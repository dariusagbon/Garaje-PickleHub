// Mobile menu toggle for the public site header.

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
