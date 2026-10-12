// Full screen for one element, with a "fill the window" fallback for browsers
// that can't (iPhone Safari). Keeps the screen awake while it is on.
// The element gets the class "is-fullscreen" while it is full screen.

export function fullscreenController(element, { onChange } = {}) {
    const canFullscreen = Boolean(element.requestFullscreen || element.webkitRequestFullscreen);
    let wakeLock = null;

    const nativeOn = () => (document.fullscreenElement || document.webkitFullscreenElement) === element;
    const isOn = () => nativeOn() || element.classList.contains('is-pseudo-fullscreen');

    async function keepScreenAwake(on) {
        try {
            if (on && !wakeLock && 'wakeLock' in navigator) wakeLock = await navigator.wakeLock.request('screen');
            if (!on && wakeLock) {
                await wakeLock.release();
                wakeLock = null;
            }
        } catch {
            // Not supported or not allowed: the screen may dim as usual.
        }
    }

    function update() {
        const on = isOn();
        element.classList.toggle('is-fullscreen', on);
        keepScreenAwake(on);
        onChange?.(on);
    }

    /** Fill the window without asking the browser (no user tap needed, e.g. after a reload). */
    function fillWindow() {
        element.classList.add('is-pseudo-fullscreen');
        document.body.classList.add('overflow-hidden');
        update();
    }

    async function enter() {
        if (canFullscreen) {
            try {
                await (element.requestFullscreen?.() ?? element.webkitRequestFullscreen());
                return;
            } catch {
                // Fall through to the window-filling fallback.
            }
        }
        fillWindow();
    }

    async function exit() {
        if (nativeOn()) {
            await (document.exitFullscreen?.() ?? document.webkitExitFullscreen());
        }
        element.classList.remove('is-pseudo-fullscreen');
        document.body.classList.remove('overflow-hidden');
        update();
    }

    document.addEventListener('fullscreenchange', update);
    document.addEventListener('webkitfullscreenchange', update);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && element.classList.contains('is-pseudo-fullscreen')) exit();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && isOn()) keepScreenAwake(true);
    });

    return { enter, exit, fillWindow, isOn, toggle: () => (isOn() ? exit() : enter()) };
}
