import {
    clampSplitPosition,
    normalizeActiveTab,
    normalizeLayout,
    normalizeSidebarTab,
} from './helpers';

/**
 * Build the shell-layout portion of the Alpine state.
 *
 * @param {import('../form-editor').ModuleFormInitialState} initialState
 * @returns {Object<string, unknown>}
 */
export const createLayoutState = (initialState) => {
    const initialLayout = normalizeLayout(initialState.layout);

    return {
        sidebarTab: normalizeSidebarTab(initialState.sidebarTab),
        activeTab: normalizeActiveTab(initialState.activeTab),
        layout: initialLayout,
        splitPosition: clampSplitPosition(initialState.splitPosition, initialLayout),
        isDragging: false,
        _dragHandlersBound: false,
        _boundOnDrag: null,
        _boundStopDragging: null,

        setLayout(nextLayout) {
            this.layout = normalizeLayout(nextLayout);
            this.splitPosition = clampSplitPosition(this.splitPosition, this.layout);
            this.stopDragging();
        },

        setSidebarTab(nextTab) {
            this.sidebarTab = normalizeSidebarTab(nextTab);
        },

        setActiveTab(nextTab) {
            this.activeTab = normalizeActiveTab(nextTab);
        },

        workspaceClass() {
            return this.layout === 'horizontal' ? 'flex-col' : 'flex-row';
        },

        editorPaneStyle() {
            if (this.layout === 'preview') {
                return { display: 'none' };
            }

            if (this.layout === 'horizontal') {
                return {
                    height: `${this.splitPosition}%`,
                    width: '100%',
                };
            }

            return {
                width: `${this.splitPosition}%`,
                minWidth: '20rem',
            };
        },

        previewPaneStyle() {
            if (this.layout === 'preview') {
                return { flex: '1 1 100%', width: '100%', height: '100%' };
            }

            if (this.layout === 'horizontal') {
                return {
                    height: `${100 - this.splitPosition}%`,
                    width: '100%',
                };
            }

            return {
                width: `${100 - this.splitPosition}%`,
            };
        },

        gutterClass() {
            return this.layout === 'horizontal'
                ? 'h-1.5 w-full cursor-row-resize'
                : 'h-full w-1.5 cursor-col-resize';
        },

        layoutButtonClass(layout) {
            return this.layout === layout
                ? 'bg-[#077CB6] text-white shadow-sm'
                : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700';
        },

        sidebarTabButtonClass(tab) {
            return this.sidebarTab === tab
                ? 'bg-[#077CB6] text-white shadow-sm'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
        },

        editorTabClass(tab) {
            return this.activeTab === tab
                ? 'border-[#077CB6] text-[#077CB6]'
                : 'border-transparent text-gray-400 hover:text-gray-700';
        },

        settingsModeButtonClass(mode) {
            return this.settingsMode === mode
                ? 'bg-[#077CB6] text-white shadow-sm'
                : 'bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900';
        },

        startDragging(event) {
            if (this.layout === 'preview') {
                return;
            }

            this.isDragging = true;

            if (!this._dragHandlersBound) {
                this._boundOnDrag = this.onDrag.bind(this);
                this._boundStopDragging = this.stopDragging.bind(this);
                this._dragHandlersBound = true;
            }

            window.addEventListener('pointermove', this._boundOnDrag);
            window.addEventListener('pointerup', this._boundStopDragging, { once: true });
            window.addEventListener('pointercancel', this._boundStopDragging, { once: true });
            this.onDrag(event);
        },

        onDrag(event) {
            if (!this.isDragging) {
                return;
            }

            const workspace = this.$refs.editorWorkspace;
            if (!(workspace instanceof HTMLElement)) {
                return;
            }

            const rect = workspace.getBoundingClientRect();
            if (rect.width <= 0 || rect.height <= 0) {
                return;
            }

            if (this.layout === 'horizontal') {
                const rawPosition = ((event.clientY - rect.top) / rect.height) * 100;
                this.splitPosition = clampSplitPosition(rawPosition, this.layout);

                return;
            }

            const rawPosition = ((event.clientX - rect.left) / rect.width) * 100;
            this.splitPosition = clampSplitPosition(rawPosition, this.layout);
        },

        stopDragging() {
            this.isDragging = false;

            if (!this._dragHandlersBound) {
                return;
            }

            window.removeEventListener('pointermove', this._boundOnDrag);
            window.removeEventListener('pointerup', this._boundStopDragging);
            window.removeEventListener('pointercancel', this._boundStopDragging);
        },
    };
};
