/**
 * @typedef {Object} ProfilePreviewConfig
 * @property {number} [totalPages] Number of pages in preview carousel.
 * @property {Array<string>} [pageNames] Ordered page labels shown in preview header.
 * @property {{fullscreen?: string, exitFullscreen?: string}} [i18n] Runtime labels.
 */

/**
 * Alpine factory for profile preview carousel behavior.
 *
 * @param {ProfilePreviewConfig} [config={}]
 * @returns {Record<string, unknown>}
 */
export function profilePreview(config = {}) {
    const i18n = {
        fullscreen: 'Fullscreen',
        exitFullscreen: 'Exit Fullscreen',
        ...(config.i18n ?? {}),
    };

    return {
        activePage: 0,
        pageMotionClass: '',
        isFullscreen: false,
        totalPages: Math.max(1, Number(config.totalPages ?? 1)),
        pageNames: Array.isArray(config.pageNames) ? config.pageNames : [],
        motionTimer: null,
        i18n,

        /**
         * Attach fullscreen observer once Alpine instance mounts.
         */
        init() {
            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
            });
        },

        /**
         * Set current page and trigger directional animation class.
         *
         * @param {number} index
         * @param {'next'|'prev'} [direction='next']
         */
        setPage(index, direction = 'next') {
            if (index < 0 || index >= this.totalPages || index === this.activePage) {
                return;
            }

            this.activePage = index;
            this.pageMotionClass = '';

            window.clearTimeout(this.motionTimer);
            requestAnimationFrame(() => {
                this.pageMotionClass = direction === 'prev'
                    ? 'profile-page-slide-prev'
                    : 'profile-page-slide-next';

                this.motionTimer = window.setTimeout(() => {
                    this.pageMotionClass = '';
                }, 320);
            });
        },

        /**
         * Navigate to the next page in a circular manner.
         */
        nextPage() {
            if (this.totalPages <= 1) {
                return;
            }

            this.setPage((this.activePage + 1) % this.totalPages, 'next');
        },

        /**
         * Navigate to the previous page in a circular manner.
         */
        previousPage() {
            if (this.totalPages <= 1) {
                return;
            }

            this.setPage((this.activePage - 1 + this.totalPages) % this.totalPages, 'prev');
        },

        /**
         * Toggle fullscreen mode for the preview wrapper.
         */
        async toggleFullscreen() {
            const target = this.$refs.viewer;
            if (!target) {
                return;
            }

            if (!document.fullscreenElement) {
                await target.requestFullscreen?.();
            } else {
                await document.exitFullscreen?.();
            }
        },
    };
}

/**
 * Expose globally for Blade `x-data="profilePreview(...)"` usage.
 */
if (typeof window !== 'undefined') {
    window.profilePreview = profilePreview;
}
