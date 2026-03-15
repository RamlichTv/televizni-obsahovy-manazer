import Alpine from 'alpinejs';

/**
 * Lightweight runtime boot file for isolated module iframes.
 *
 * Module previews and saved runtime cells only need Alpine support for
 * declarative template behavior, so this entrypoint intentionally avoids the
 * rest of the application shell boot logic from `app.js`.
 */
window.Alpine = Alpine;

const MODULE_RUNTIME_ACTIVITY_TYPE = 'tom-module-runtime-activity';

/**
 * Relay runtime activity to the parent monitor shell.
 *
 * Saved modules render inside an iframe-backed document, so the outer screen
 * page cannot observe pointer/keyboard activity here unless we forward it.
 */
const notifyParentActivity = (reason = 'interaction') => {
    if (window.parent === window) {
        return;
    }

    try {
        window.parent.postMessage({
            type: MODULE_RUNTIME_ACTIVITY_TYPE,
            source: 'tom-module-runtime',
            reason,
        }, '*');
    } catch (error) {
        // Ignore bridge failures; activity forwarding is best-effort only.
    }
};

/**
 * Attach listeners to nested embedded iframes so monitor timers still reset
 * when interaction happens on the iframe surface or inside same-origin embeds.
 */
const initEmbeddedFrameActivityBridge = () => {
    document.querySelectorAll('iframe').forEach((iframe) => {
        if (!(iframe instanceof HTMLIFrameElement) || iframe.dataset.monitorActivityBridgeReady === '1') {
            return;
        }

        const relay = (reason) => notifyParentActivity(reason);
        const pointerEvents = ['pointerdown', 'pointerenter', 'touchstart'];

        pointerEvents.forEach((eventName) => {
            iframe.addEventListener(eventName, () => relay(`embedded-${eventName}`), { passive: true });
        });

        iframe.addEventListener('focus', () => relay('embedded-focus'));
        iframe.addEventListener('load', () => {
            relay('embedded-load');
            attachSameOriginEmbedActivity(iframe);
        });

        iframe.dataset.monitorActivityBridgeReady = '1';
        attachSameOriginEmbedActivity(iframe);
    });
};

/**
 * Bridge actual inner-document events when the embedded page is same-origin.
 *
 * Cross-origin documents are intentionally ignored here; the browser blocks
 * DOM access, so we rely on iframe-surface activity in that case.
 */
const attachSameOriginEmbedActivity = (iframe) => {
    try {
        const innerWindow = iframe.contentWindow;
        const innerDocument = innerWindow?.document;

        if (!(innerWindow instanceof Window) || !(innerDocument instanceof Document)) {
            return;
        }

        if (innerDocument.documentElement?.dataset.monitorActivityBridgeReady === '1') {
            return;
        }

        const relay = () => notifyParentActivity('embedded-same-origin');
        const eventMap = {
            pointerdown: { capture: true, passive: true },
            keydown: { capture: true },
            wheel: { capture: true, passive: true },
            touchstart: { capture: true, passive: true },
        };

        Object.entries(eventMap).forEach(([eventName, options]) => {
            innerDocument.addEventListener(eventName, relay, options);
        });

        innerWindow.addEventListener('focus', relay);
        innerDocument.documentElement.dataset.monitorActivityBridgeReady = '1';
    } catch (error) {
        // Cross-origin embeds land here; surface listeners above still cover them.
    }
};

/**
 * Forward activity from this runtime iframe to the monitor page.
 */
const initMonitorActivityBridge = () => {
    const runtimeEvents = {
        pointerdown: { capture: true, passive: true },
        keydown: { capture: true },
        wheel: { capture: true, passive: true },
        touchstart: { capture: true, passive: true },
    };

    Object.entries(runtimeEvents).forEach(([eventName, options]) => {
        document.addEventListener(eventName, () => notifyParentActivity(eventName), options);
    });

    window.addEventListener('focus', () => notifyParentActivity('runtime-focus'));
    initEmbeddedFrameActivityBridge();

    if ('MutationObserver' in window) {
        const observer = new MutationObserver(() => {
            initEmbeddedFrameActivityBridge();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }
};

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
initMonitorActivityBridge();
initModuleEmbedFits();
