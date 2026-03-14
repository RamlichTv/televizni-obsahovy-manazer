/**
 * Global feedback system for light-themed toasts and modals.
 *
 * This module centralizes three behaviors:
 * 1. Dispatching toast notifications from JavaScript.
 * 2. Opening promise-based alert / confirm dialogs.
 * 3. Intercepting confirmable form submissions via data attributes.
 */

/**
 * @typedef {Object} ToastPayload
 * @property {'success'|'error'|'warning'|'info'} [type] Visual tone for the toast card.
 * @property {string} [message] Human-readable notification text.
 */

/**
 * @typedef {Object} ModalPayload
 * @property {string} [id] Stable request identifier used to resolve a modal promise.
 * @property {'alert'|'confirm'} [type] Modal mode that controls footer actions.
 * @property {string} [title] Heading rendered in the shared modal shell.
 * @property {string} [message] Body copy rendered in the shared modal shell.
 * @property {string} [confirmLabel] Label for the primary action button.
 * @property {string} [cancelLabel] Label for the secondary action button.
 * @property {'primary'|'danger'} [confirmVariant] Primary button style for confirm flows.
 * @property {string} [name] Named modal identifier used by reusable page dialogs.
 */

/**
 * Dataset attribute used to bypass the confirm interceptor on approved re-submit.
 *
 * @type {string}
 */
const CONFIRM_APPROVED_FLAG = 'feedbackConfirmApproved';

/**
 * Body dataset key used to keep scroll locking correct while multiple modals stack.
 *
 * @type {string}
 */
const BODY_LOCK_COUNT_KEY = 'feedbackModalLockCount';

/**
 * Runtime stack of open modal keys so only the top-most dialog traps focus.
 *
 * @type {string[]}
 */
const activeModalStack = [];

/**
 * Incrementing counter for promise-backed system modals.
 *
 * @type {number}
 */
let systemModalSequence = 0;

/**
 * One-time guard for the document-level form submit interceptor.
 *
 * @type {boolean}
 */
let feedbackSystemInitialized = false;

/**
 * Return a normalized toast payload or null when no message is available.
 *
 * @param {ToastPayload} [detail={}] Candidate toast detail from Blade or JS.
 * @returns {ToastPayload|null} Sanitized toast payload ready for dispatch.
 */
function normalizeToastPayload(detail = {}) {
    const message = String(detail.message ?? '').trim();
    if (message === '') {
        return null;
    }

    const type = ['success', 'error', 'warning', 'info'].includes(detail.type)
        ? detail.type
        : 'info';

    return { type, message };
}

/**
 * Dispatch one custom window event when the browser runtime is available.
 *
 * @param {string} name Event name consumed by the Alpine feedback hosts.
 * @param {Record<string, unknown>} detail Structured event payload.
 * @returns {void}
 */
function dispatchFeedbackEvent(name, detail) {
    if (typeof window === 'undefined') {
        return;
    }

    window.dispatchEvent(new CustomEvent(name, { detail }));
}

/**
 * Return every focusable descendant inside one modal panel.
 *
 * @param {HTMLElement|null|undefined} root Modal panel element.
 * @returns {HTMLElement[]} Ordered list of focusable elements.
 */
function focusableElements(root) {
    if (!(root instanceof HTMLElement)) {
        return [];
    }

    const selector = 'a[href], button, input:not([type="hidden"]), textarea, select, details, [tabindex]:not([tabindex="-1"])';

    return Array.from(root.querySelectorAll(selector)).filter((element) => {
        if (!(element instanceof HTMLElement)) {
            return false;
        }

        return !element.hasAttribute('disabled') && element.getAttribute('aria-hidden') !== 'true';
    });
}

/**
 * Increase the shared modal body-lock counter.
 *
 * This keeps background scrolling disabled while one or more dialogs are open.
 *
 * @returns {void}
 */
function lockBodyScroll() {
    if (typeof document === 'undefined') {
        return;
    }

    const body = document.body;
    const nextCount = Number(body.dataset[BODY_LOCK_COUNT_KEY] ?? 0) + 1;

    body.dataset[BODY_LOCK_COUNT_KEY] = String(nextCount);
    body.classList.add('overflow-y-hidden');
}

/**
 * Decrease the shared modal body-lock counter.
 *
 * @returns {void}
 */
function unlockBodyScroll() {
    if (typeof document === 'undefined') {
        return;
    }

    const body = document.body;
    const currentCount = Number(body.dataset[BODY_LOCK_COUNT_KEY] ?? 0);
    const nextCount = Math.max(0, currentCount - 1);

    body.dataset[BODY_LOCK_COUNT_KEY] = String(nextCount);

    if (nextCount === 0) {
        body.classList.remove('overflow-y-hidden');
    }
}

/**
 * Register one modal key as the active top-most dialog.
 *
 * @param {string} key Unique modal instance key.
 * @returns {void}
 */
function pushModalKey(key) {
    const existingIndex = activeModalStack.indexOf(key);
    if (existingIndex !== -1) {
        activeModalStack.splice(existingIndex, 1);
    }

    activeModalStack.push(key);
}

/**
 * Remove one modal key from the active modal stack.
 *
 * @param {string} key Unique modal instance key.
 * @returns {void}
 */
function removeModalKey(key) {
    const existingIndex = activeModalStack.indexOf(key);
    if (existingIndex !== -1) {
        activeModalStack.splice(existingIndex, 1);
    }
}

/**
 * Determine whether the supplied modal key is currently top-most.
 *
 * @param {string} key Unique modal instance key.
 * @returns {boolean} True when this modal should own keyboard interactions.
 */
function isTopModalKey(key) {
    return activeModalStack.at(-1) === key;
}

/**
 * Generate the next request identifier for one system modal promise.
 *
 * @returns {string} Monotonic request identifier.
 */
function nextSystemModalId() {
    systemModalSequence += 1;

    return `app-system-modal-${systemModalSequence}`;
}

/**
 * Dispatch one toast event for the global toast host.
 *
 * @param {ToastPayload} [detail={}] Toast metadata.
 * @returns {void}
 */
export function appToast(detail = {}) {
    const payload = normalizeToastPayload(detail);

    if (!payload) {
        return;
    }

    dispatchFeedbackEvent('app-toast', payload);
}

/**
 * Open one promise-backed alert dialog.
 *
 * @param {ModalPayload} [detail={}] Alert metadata.
 * @returns {Promise<void>} Resolves after the alert is dismissed.
 */
export function appAlert(detail = {}) {
    return openSystemModal({
        ...detail,
        type: 'alert',
    }).then(() => undefined);
}

/**
 * Open one promise-backed confirmation dialog.
 *
 * @param {ModalPayload} [detail={}] Confirmation metadata.
 * @returns {Promise<boolean>} True when the primary action is confirmed.
 */
export function appConfirm(detail = {}) {
    return openSystemModal({
        ...detail,
        type: 'confirm',
    }).then((result) => Boolean(result.confirmed));
}

/**
 * Dispatch one system modal request and resolve it from the response event.
 *
 * @param {ModalPayload} detail Modal metadata.
 * @returns {Promise<{id: string, confirmed: boolean}>} Promise resolved by the system modal host.
 */
function openSystemModal(detail) {
    const requestId = String(detail.id ?? nextSystemModalId());

    return new Promise((resolve) => {
        /**
         * Resolve only the matching modal response.
         *
         * @param {CustomEvent<{id?: string, confirmed?: boolean}>} event Modal response event.
         * @returns {void}
         */
        const handleResponse = (event) => {
            const response = event.detail ?? {};

            if (String(response.id ?? '') !== requestId) {
                return;
            }

            window.removeEventListener('modal-response', handleResponse);

            resolve({
                id: requestId,
                confirmed: Boolean(response.confirmed),
            });
        };

        window.addEventListener('modal-response', handleResponse);

        dispatchFeedbackEvent('open-modal', {
            id: requestId,
            type: detail.type === 'confirm' ? 'confirm' : 'alert',
            title: String(detail.title ?? ''),
            message: String(detail.message ?? ''),
            confirmLabel: String(detail.confirmLabel ?? (detail.type === 'confirm' ? 'Confirm' : 'OK')),
            cancelLabel: String(detail.cancelLabel ?? 'Cancel'),
            confirmVariant: detail.confirmVariant === 'danger' ? 'danger' : 'primary',
        });
    });
}

/**
 * Build Alpine state for the singleton toast host.
 *
 * @param {ToastPayload|null} [initialToast=null] Optional server-rendered flash payload.
 * @returns {Record<string, unknown>} Alpine-compatible state object.
 */
export function appToastHost(initialToast = null) {
    return {
        isVisible: false,
        toast: null,
        dismissTimer: null,
        boundToastHandler: null,

        /**
         * Start event listeners and render the initial server toast when present.
         *
         * @returns {void}
         */
        init() {
            this.boundToastHandler = (event) => {
                const payload = normalizeToastPayload(event.detail ?? {});

                if (!payload) {
                    return;
                }

                this.showToast(payload);
            };

            window.addEventListener('app-toast', this.boundToastHandler);

            const payload = normalizeToastPayload(initialToast ?? {});
            if (payload) {
                this.showToast(payload);
            }
        },

        /**
         * Tear down listeners and pending timers when Alpine unmounts the host.
         *
         * @returns {void}
         */
        destroy() {
            if (this.boundToastHandler) {
                window.removeEventListener('app-toast', this.boundToastHandler);
            }

            this.clearTimer();
        },

        /**
         * Show one toast and restart the auto-dismiss lifecycle.
         *
         * @param {ToastPayload} payload Toast detail to present.
         * @returns {void}
         */
        showToast(payload) {
            this.clearTimer();
            this.toast = payload;
            this.isVisible = true;
            this.dismissTimer = window.setTimeout(() => this.closeToast(), 4000);
        },

        /**
         * Hide the current toast and reset its dismiss timer.
         *
         * @returns {void}
         */
        closeToast() {
            this.clearTimer();
            this.isVisible = false;
        },

        /**
         * Clear the active dismiss timer when one exists.
         *
         * @returns {void}
         */
        clearTimer() {
            if (this.dismissTimer !== null) {
                window.clearTimeout(this.dismissTimer);
                this.dismissTimer = null;
            }
        },
    };
}

/**
 * Build Alpine state for one reusable named modal shell.
 *
 * @param {{name?: string, show?: boolean}} [config={}] Named modal configuration.
 * @returns {Record<string, unknown>} Alpine-compatible state object.
 */
export function appNamedModal(config = {}) {
    const name = String(config.name ?? '');
    const show = Boolean(config.show);
    const stackKey = `named-modal:${name}:${Math.random().toString(36).slice(2)}`;

    return {
        name,
        show,
        stackKey,
        lastFocusedElement: null,
        boundOpenHandler: null,
        boundCloseHandler: null,

        /**
         * Register global modal events and honor the initial server-side open state.
         *
         * @returns {void}
         */
        init() {
            this.boundOpenHandler = (event) => {
                if (String(event.detail?.name ?? '') !== this.name) {
                    return;
                }

                this.open();
            };

            this.boundCloseHandler = (event) => {
                if (String(event.detail?.name ?? '') !== this.name) {
                    return;
                }

                this.close();
            };

            window.addEventListener('open-modal', this.boundOpenHandler);
            window.addEventListener('close-modal', this.boundCloseHandler);

            this.$watch('show', (value, previous) => {
                if (value === previous) {
                    return;
                }

                if (value) {
                    this.activate();
                    this.focusFirstElement();
                    return;
                }

                this.deactivate();
            });

            if (this.show) {
                this.activate();
                this.focusFirstElement();
            }
        },

        /**
         * Remove modal event listeners and release any active modal lock.
         *
         * @returns {void}
         */
        destroy() {
            if (this.boundOpenHandler) {
                window.removeEventListener('open-modal', this.boundOpenHandler);
            }

            if (this.boundCloseHandler) {
                window.removeEventListener('close-modal', this.boundCloseHandler);
            }

            if (this.show) {
                this.deactivate();
            }
        },

        /**
         * Open this named modal instance.
         *
         * @returns {void}
         */
        open() {
            this.show = true;
        },

        /**
         * Close this named modal instance.
         *
         * @returns {void}
         */
        close() {
            this.show = false;
        },

        /**
         * Register this dialog as active and lock page scrolling.
         *
         * @returns {void}
         */
        activate() {
            this.lastFocusedElement = document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;

            pushModalKey(this.stackKey);
            lockBodyScroll();
        },

        /**
         * Release modal state and restore focus to the triggering element.
         *
         * @returns {void}
         */
        deactivate() {
            removeModalKey(this.stackKey);
            unlockBodyScroll();

            if (this.lastFocusedElement instanceof HTMLElement) {
                this.lastFocusedElement.focus({ preventScroll: true });
            }
        },

        /**
         * Determine whether this dialog currently owns keyboard interactions.
         *
         * @returns {boolean} True when this is the top-most open modal.
         */
        isTopModal() {
            return isTopModalKey(this.stackKey);
        },

        /**
         * Return focusable descendants inside the current modal panel.
         *
         * @returns {HTMLElement[]} Ordered focusable elements.
         */
        focusables() {
            return focusableElements(this.$refs.panel);
        },

        /**
         * Focus the first available element inside the dialog.
         *
         * @returns {void}
         */
        focusFirstElement() {
            this.$nextTick(() => {
                if (!this.show || !this.isTopModal()) {
                    return;
                }

                const target = this.focusables()[0] ?? this.$refs.panel;
                target?.focus?.({ preventScroll: true });
            });
        },

        /**
         * Trap focus inside the top-most dialog while tab navigation is active.
         *
         * @param {KeyboardEvent} event Keyboard event from the window listener.
         * @returns {void}
         */
        handleTab(event) {
            if (!this.show || !this.isTopModal()) {
                return;
            }

            const focusables = this.focusables();

            if (focusables.length === 0) {
                event.preventDefault();
                this.$refs.panel?.focus?.({ preventScroll: true });
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const activeElement = document.activeElement;

            if (event.shiftKey && activeElement === first) {
                event.preventDefault();
                last.focus();
                return;
            }

            if (!event.shiftKey && activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    };
}

/**
 * Build Alpine state for the singleton system alert / confirm host.
 *
 * @returns {Record<string, unknown>} Alpine-compatible state object.
 */
export function appSystemModal() {
    const stackKey = 'system-modal';

    return {
        isOpen: false,
        requestId: null,
        title: '',
        message: '',
        type: 'alert',
        confirmLabel: 'OK',
        cancelLabel: 'Cancel',
        confirmVariant: 'primary',
        lastFocusedElement: null,
        boundOpenHandler: null,

        /**
         * Register the global system-modal listener.
         *
         * @returns {void}
         */
        init() {
            this.boundOpenHandler = (event) => {
                const detail = event.detail ?? {};

                if (!['alert', 'confirm'].includes(String(detail.type ?? ''))) {
                    return;
                }

                this.open(detail);
            };

            window.addEventListener('open-modal', this.boundOpenHandler);
        },

        /**
         * Remove the modal listener and release any active lock.
         *
         * @returns {void}
         */
        destroy() {
            if (this.boundOpenHandler) {
                window.removeEventListener('open-modal', this.boundOpenHandler);
            }

            if (this.isOpen) {
                this.release();
            }
        },

        /**
         * Open or replace the active system modal request.
         *
         * @param {ModalPayload} detail Incoming modal detail payload.
         * @returns {void}
         */
        open(detail) {
            if (this.isOpen) {
                this.respond(false);
            }

            this.requestId = String(detail.id ?? nextSystemModalId());
            this.title = String(detail.title ?? '');
            this.message = String(detail.message ?? '');
            this.type = detail.type === 'confirm' ? 'confirm' : 'alert';
            this.confirmLabel = String(detail.confirmLabel ?? (this.type === 'confirm' ? 'Confirm' : 'OK'));
            this.cancelLabel = String(detail.cancelLabel ?? 'Cancel');
            this.confirmVariant = detail.confirmVariant === 'danger' ? 'danger' : 'primary';
            this.isOpen = true;

            this.activate();
            this.focusPrimaryAction();
        },

        /**
         * Register the system modal as active and lock page scroll.
         *
         * @returns {void}
         */
        activate() {
            this.lastFocusedElement = document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;

            pushModalKey(stackKey);
            lockBodyScroll();
        },

        /**
         * Release the active modal lock and restore prior focus.
         *
         * @returns {void}
         */
        release() {
            removeModalKey(stackKey);
            unlockBodyScroll();

            if (this.lastFocusedElement instanceof HTMLElement) {
                this.lastFocusedElement.focus({ preventScroll: true });
            }
        },

        /**
         * Determine whether the system host owns the keyboard right now.
         *
         * @returns {boolean} True when this dialog is top-most.
         */
        isTopModal() {
            return isTopModalKey(stackKey);
        },

        /**
         * Return focusable controls from the rendered dialog.
         *
         * @returns {HTMLElement[]} Ordered focusable elements.
         */
        focusables() {
            return focusableElements(this.$refs.panel);
        },

        /**
         * Focus the primary action button after Alpine renders the dialog.
         *
         * @returns {void}
         */
        focusPrimaryAction() {
            this.$nextTick(() => {
                if (!this.isOpen || !this.isTopModal()) {
                    return;
                }

                const target = this.$refs.confirmButton ?? this.focusables()[0] ?? this.$refs.panel;
                target?.focus?.({ preventScroll: true });
            });
        },

        /**
         * Close the dialog as a cancelled action.
         *
         * @returns {void}
         */
        cancel() {
            this.respond(false);
        },

        /**
         * Close the dialog as a confirmed action.
         *
         * @returns {void}
         */
        confirm() {
            this.respond(true);
        },

        /**
         * Resolve the active modal request and emit the response event.
         *
         * @param {boolean} confirmed Final confirmation state.
         * @returns {void}
         */
        respond(confirmed) {
            if (!this.isOpen || !this.requestId) {
                return;
            }

            const resolvedId = this.requestId;

            this.isOpen = false;
            this.requestId = null;
            this.release();

            dispatchFeedbackEvent('modal-response', {
                id: resolvedId,
                confirmed,
            });
        },

        /**
         * Trap focus inside the top-most system modal.
         *
         * @param {KeyboardEvent} event Keyboard event from the window listener.
         * @returns {void}
         */
        handleTab(event) {
            if (!this.isOpen || !this.isTopModal()) {
                return;
            }

            const focusables = this.focusables();

            if (focusables.length === 0) {
                event.preventDefault();
                this.$refs.panel?.focus?.({ preventScroll: true });
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const activeElement = document.activeElement;

            if (event.shiftKey && activeElement === first) {
                event.preventDefault();
                last.focus();
                return;
            }

            if (!event.shiftKey && activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },

        /**
         * Return the primary button classes for the active modal tone.
         *
         * @returns {string} Tailwind utility string for the primary action button.
         */
        confirmButtonClasses() {
            if (this.confirmVariant === 'danger') {
                return 'inline-flex items-center justify-center rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-200 focus:ring-offset-2';
            }

            return 'inline-flex items-center justify-center rounded-md border border-transparent bg-[#077CB6] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#066a9b] focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2';
        },
    };
}

/**
 * Intercept declarative confirm forms and route them through the shared modal.
 *
 * @param {SubmitEvent} event Native submit event captured at the document level.
 * @returns {void}
 */
function interceptConfirmableFormSubmit(event) {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.dataset[CONFIRM_APPROVED_FLAG] === 'true') {
        delete form.dataset[CONFIRM_APPROVED_FLAG];
        return;
    }

    const message = String(form.dataset.confirmMessage ?? '').trim();
    if (message === '') {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;

    appConfirm({
        title: String(form.dataset.confirmTitle ?? 'Please confirm'),
        message,
        confirmLabel: form.dataset.confirmLabel,
        cancelLabel: form.dataset.cancelLabel,
        confirmVariant: form.dataset.confirmVariant === 'danger' ? 'danger' : 'primary',
    }).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        if (typeof form.requestSubmit === 'function') {
            form.dataset[CONFIRM_APPROVED_FLAG] = 'true';

            if (submitter) {
                form.requestSubmit(submitter);
                return;
            }

            form.requestSubmit();
            return;
        }

        HTMLFormElement.prototype.submit.call(form);
    });
}

/**
 * Register the shared feedback form interception layer once per page load.
 *
 * @returns {void}
 */
export function initializeFeedbackSystem() {
    if (feedbackSystemInitialized || typeof document === 'undefined') {
        return;
    }

    feedbackSystemInitialized = true;
    document.addEventListener('submit', interceptConfirmableFormSubmit, true);
}

/**
 * Expose the public feedback helpers for Blade-driven Alpine templates.
 */
if (typeof window !== 'undefined') {
    window.appToast = appToast;
    window.appAlert = appAlert;
    window.appConfirm = appConfirm;
    window.appToastHost = appToastHost;
    window.appNamedModal = appNamedModal;
    window.appSystemModal = appSystemModal;
}
