import Alpine from 'alpinejs';

/**
 * Lightweight runtime boot file for isolated module iframes.
 *
 * Module previews and saved runtime cells only need Alpine support for
 * declarative template behavior, so this entrypoint intentionally avoids the
 * rest of the application shell boot logic from `app.js`.
 */
window.Alpine = Alpine;

/**
 * Scale embedded iframes into a fixed virtual canvas so they read like a
 * presentation surface instead of a scrollable browser window.
 *
 * The behavior is best-effort only. We cannot measure cross-origin document
 * contents, so we scale a chosen virtual viewport to the available container.
 */
const initModuleEmbedFits = () => {
    document.querySelectorAll('[data-module-embed-fit]').forEach((root) => {
        if (!(root instanceof HTMLElement) || root.dataset.embedFitReady === '1') {
            return;
        }

        const viewport = root.querySelector('[data-embed-fit-viewport]');
        const canvas = root.querySelector('[data-embed-fit-canvas]');
        if (!(viewport instanceof HTMLElement) || !(canvas instanceof HTMLElement)) {
            return;
        }

        const virtualWidth = Math.max(320, Number.parseInt(root.dataset.embedFitWidth || '1440', 10) || 1440);
        const virtualHeight = Math.max(240, Number.parseInt(root.dataset.embedFitHeight || '900', 10) || 900);
        const minScale = Math.max(0.1, Number.parseFloat(root.dataset.embedFitMinScale || '0.22') || 0.22);

        const update = () => {
            const widthScale = viewport.clientWidth / virtualWidth;
            const heightScale = viewport.clientHeight / virtualHeight;
            const nextScale = Math.max(minScale, Math.min(widthScale, heightScale));

            canvas.style.width = `${virtualWidth}px`;
            canvas.style.height = `${virtualHeight}px`;
            canvas.style.transform = `translate(-50%, -50%) scale(${nextScale})`;
        };

        update();

        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(update);
            observer.observe(viewport);
        } else {
            window.addEventListener('resize', update);
        }

        root.dataset.embedFitReady = '1';
    });
};

Alpine.start();
initModuleEmbedFits();
