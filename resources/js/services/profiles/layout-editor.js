/**
 * @typedef {Object} ProfileLayoutEditorConfig
 * @property {Array<Object>} [pages] Initial page payload rendered by backend.
 * @property {Array<Object>} [modules] Module catalog available for drag/drop.
 * @property {Object<string, string>} [routes] API endpoints with placeholders for page/module ids.
 * @property {string} [csrfToken] Token forwarded in every mutating request.
 * @property {number} [maxTracks] Upper limit for rows/columns in one page grid.
 * @property {Object<string, string>} [i18n] Runtime labels/messages injected from Blade translations.
 */

/**
 * Build Alpine state for the profile layout editor.
 *
 * The factory is exported for module imports and also attached to `window`
 * so Blade templates can reference it directly inside `x-data`.
 *
 * @param {ProfileLayoutEditorConfig} [config={}] Runtime configuration from Blade.
 * @returns {Record<string, unknown>} Alpine component state + actions.
 */
export function profileLayoutEditor(config = {}) {
    const i18n = {
        pageNamePrompt: 'Page name',
        pageAdded: 'Page added to profile.',
        atLeastOnePage: 'At least one page must remain.',
        confirmDeletePage: 'Delete current page?',
        pageRemoved: 'Page removed.',
        maxRowsReached: 'Maximum row count reached.',
        rowOccupied: 'Cannot remove row because a module occupies it.',
        maxColumnsReached: 'Maximum column count reached.',
        columnOccupied: 'Cannot remove column because a module occupies it.',
        moduleAdded: 'Module added to page.',
        modulePlacementUpdated: 'Module placement updated.',
        confirmRemoveModule: 'Remove this module from the page?',
        moduleRemoved: 'Module removed from page.',
        rowSpanExceeded: 'Row span exceeds page bounds.',
        columnSpanExceeded: 'Column span exceeds page bounds.',
        pageUpdated: 'Page updated.',
        requestFailed: 'Request failed.',
        uncategorized: 'Uncategorized',
        ...(config.i18n ?? {}),
    };

    return {
        pages: Array.isArray(config.pages) ? config.pages : [],
        modules: Array.isArray(config.modules) ? config.modules : [],
        routes: config.routes ?? {},
        csrfToken: config.csrfToken ?? '',
        maxTracks: Number(config.maxTracks ?? 24),
        activePageIndex: 0,
        pageMotionClass: '',
        motionTimer: null,
        isFullscreen: false,
        sidebarOpen: true,
        moduleSearch: '',
        moduleCategoryOpen: {},
        selectedPlacementId: null,
        dragPayload: null,
        isSaving: false,
        resizeState: null,
        boundResizeMove: null,
        boundResizeEnd: null,

        // Initialize runtime-only handlers and normalize incoming payload shape.
        init() {
            this.pages = this.pages.map((page) => this.normalizePage(page));
            this.modules = this.normalizeModules(this.modules);
            this.initializeModuleCategoryState();
            if (this.pages.length === 0) {
                this.pages.push(this.normalizePage({
                    id: Date.now(),
                    name: 'Page 1',
                    row_count: 1,
                    column_count: 1,
                    row_sizes: [100],
                    column_sizes: [100],
                    placements: [],
                    duration: null,
                    is_active: true,
                    order: 1,
                }));
            }

            this.boundResizeMove = (event) => this.handleResizeMove(event);
            this.boundResizeEnd = () => this.endResize();
            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
            });
        },

        // Return currently selected page or null when state is not yet ready.
        activePage() {
            return this.pages[this.activePageIndex] ?? null;
        },

        // Normalize page data coming from API responses into consistent numeric fields.
        normalizePage(page) {
            const rowCount = Math.max(1, Number(page.row_count ?? 1));
            const columnCount = Math.max(1, Number(page.column_count ?? 1));

            return {
                id: page.id,
                name: page.name ?? 'Page',
                order: Number(page.order ?? 0),
                duration: page.duration === null ? null : Number(page.duration),
                is_active: Boolean(page.is_active),
                row_count: rowCount,
                column_count: columnCount,
                row_sizes: this.normalizeTrackSizes(page.row_sizes ?? [], rowCount),
                column_sizes: this.normalizeTrackSizes(page.column_sizes ?? [], columnCount),
                placements: this.normalizePlacements(page.placements ?? []),
            };
        },

        // Ensure every placement has numeric coordinates and safe defaults.
        normalizePlacements(placements) {
            return placements.map((placement) => ({
                id: Number(placement.id),
                module_id: String(placement.module_id),
                module_name: placement.module_name ?? 'Module',
                module_category: placement.module_category ?? '',
                row_index: Number(placement.row_index ?? 1),
                column_index: Number(placement.column_index ?? 1),
                row_span: Math.max(1, Number(placement.row_span ?? 1)),
                column_span: Math.max(1, Number(placement.column_span ?? 1)),
                order: Number(placement.order ?? 0),
                config_overrides: placement.config_overrides ?? {},
                rendered_html: placement.rendered_html ?? '',
            }));
        },

        // Normalize the draggable module catalog for consistent grouping and search.
        normalizeModules(modules) {
            return modules.map((module) => ({
                id: String(module.id ?? ''),
                name: String(module.name ?? 'Module'),
                category: String(module.category ?? '').trim(),
            }));
        },

        // Rebalance row/column sizes so they always sum to 100%.
        normalizeTrackSizes(sizes, count) {
            if (count <= 0) {
                return [];
            }

            const clean = Array.isArray(sizes)
                ? sizes.map((value) => Math.max(1, Number(value || 0))).slice(0, count)
                : [];

            while (clean.length < count) {
                clean.push(100 / count);
            }

            const total = clean.reduce((carry, value) => carry + value, 0);
            if (total <= 0) {
                return Array.from({ length: count }, () => Number((100 / count).toFixed(4)));
            }

            return clean.map((value) => Number(((value / total) * 100).toFixed(4)));
        },

        // Seed open/closed dropdown state for every category represented in the module catalog.
        initializeModuleCategoryState() {
            const nextState = { ...this.moduleCategoryOpen };

            this.modules.forEach((module) => {
                const categoryKey = this.moduleCategoryKey(module);
                if (typeof nextState[categoryKey] === 'undefined') {
                    nextState[categoryKey] = true;
                }
            });

            this.moduleCategoryOpen = nextState;
        },

        // Resolve a stable internal key for one module category.
        moduleCategoryKey(module) {
            const category = String(module.category ?? '').trim().toLowerCase();

            return category === '' ? '__uncategorized__' : category;
        },

        // Resolve the user-facing label for one module category.
        moduleCategoryLabel(module) {
            const category = String(module.category ?? '').trim();

            return category === '' ? i18n.uncategorized : category;
        },

        // Perform live search in module sidebar by name and category.
        filteredModules() {
            const search = this.moduleSearch.trim().toLowerCase();
            if (search === '') {
                return this.modules;
            }

            return this.modules.filter((module) => {
                const name = String(module.name ?? '').toLowerCase();
                const category = String(module.category ?? '').toLowerCase();

                return name.includes(search) || category.includes(search);
            });
        },

        // Group current search results into per-category dropdown sections.
        groupedFilteredModules() {
            const groups = new Map();

            this.filteredModules().forEach((module) => {
                const key = this.moduleCategoryKey(module);
                if (!groups.has(key)) {
                    groups.set(key, {
                        key,
                        label: this.moduleCategoryLabel(module),
                        modules: [],
                    });
                }

                groups.get(key).modules.push(module);
            });

            return Array.from(groups.values())
                .sort((left, right) => {
                    if (left.key === '__uncategorized__') {
                        return 1;
                    }
                    if (right.key === '__uncategorized__') {
                        return -1;
                    }

                    return left.label.localeCompare(right.label);
                })
                .map((group) => ({
                    ...group,
                    modules: [...group.modules].sort((left, right) => left.name.localeCompare(right.name)),
                }));
        },

        // Toggle visibility of the left rail that contains page tabs and the module library.
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        // Expand or collapse one module category group.
        toggleModuleCategory(categoryKey) {
            this.moduleCategoryOpen = {
                ...this.moduleCategoryOpen,
                [categoryKey]: !this.isModuleCategoryOpen(categoryKey),
            };
        },

        // Automatically reveal matching groups while the user is searching.
        isModuleCategoryOpen(categoryKey) {
            if (this.moduleSearch.trim() !== '') {
                return true;
            }

            return Boolean(this.moduleCategoryOpen[categoryKey]);
        },

        // Switch active page and trigger directional transition class.
        setActivePage(index, direction = null) {
            if (index < 0 || index >= this.pages.length) {
                return;
            }

            if (index === this.activePageIndex) {
                this.selectedPlacementId = null;
                return;
            }

            const inferredDirection = direction ?? (index > this.activePageIndex ? 'next' : 'prev');
            this.activePageIndex = index;
            this.selectedPlacementId = null;
            this.triggerPageTransition(inferredDirection);
        },

        nextPage() {
            if (this.pages.length <= 1) {
                return;
            }

            this.setActivePage((this.activePageIndex + 1) % this.pages.length, 'next');
        },

        previousPage() {
            if (this.pages.length <= 1) {
                return;
            }

            this.setActivePage((this.activePageIndex - 1 + this.pages.length) % this.pages.length, 'prev');
        },

        // Apply one-off CSS class to animate page changes.
        triggerPageTransition(direction = 'next') {
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

        // Toggle fullscreen mode for the editor viewport.
        async toggleFullscreen() {
            const target = this.$refs.editorViewport;
            if (!target) {
                return;
            }

            if (!document.fullscreenElement) {
                await target.requestFullscreen?.();
            } else {
                await document.exitFullscreen?.();
            }
        },

        // Create a new page via API and move focus to the newly inserted page.
        async addPage() {
            const nextNumber = this.pages.length + 1;
            const name = window.prompt(i18n.pageNamePrompt, `Page ${nextNumber}`);
            if (name === null || name.trim() === '') {
                return;
            }

            try {
                const payload = {
                    name: name.trim(),
                    row_count: 1,
                    column_count: 1,
                    duration: null,
                    is_active: true,
                };

                const result = await this.request(this.routes.pageStore, 'POST', payload);
                if (result.page) {
                    this.pages.push(this.normalizePage(result.page));
                    this.activePageIndex = this.pages.length - 1;
                    this.flashSuccess(result.message ?? i18n.pageAdded);
                }
            } catch (error) {
                this.flashError(error.message);
            }
        },

        // Delete current page (while preserving at least one page in profile).
        async removeActivePage() {
            const page = this.activePage();
            if (!page || this.pages.length <= 1) {
                this.flashError(i18n.atLeastOnePage);
                return;
            }

            const confirmed = await window.appConfirm({
                title: i18n.deletePageTitle ?? 'Delete Page',
                message: i18n.confirmDeletePage,
                confirmLabel: i18n.deleteAction ?? 'Delete',
                cancelLabel: i18n.cancelAction ?? 'Cancel',
                confirmVariant: 'danger',
            });

            if (!confirmed) {
                return;
            }

            try {
                const result = await this.request(this.pageDestroyUrl(page.id), 'DELETE');
                this.pages = this.pages.filter((candidate) => candidate.id !== page.id);
                this.activePageIndex = Math.max(0, this.activePageIndex - 1);
                this.flashSuccess(result.message ?? i18n.pageRemoved);
            } catch (error) {
                this.flashError(error.message);
            }
        },

        // Increase row count and persist immediately.
        async addRow() {
            const page = this.activePage();
            if (!page) {
                return;
            }
            if (page.row_count >= this.maxTracks) {
                this.flashError(i18n.maxRowsReached);
                return;
            }

            page.row_count += 1;
            page.row_sizes = this.addTrack(page.row_sizes, page.row_count);
            await this.persistActivePage();
        },

        // Remove last row only when no placement intersects that track.
        async removeRow() {
            const page = this.activePage();
            if (!page || page.row_count <= 1) {
                return;
            }

            const lastRow = page.row_count;
            const blocked = page.placements.some((placement) => (placement.row_index + placement.row_span - 1) >= lastRow);
            if (blocked) {
                this.flashError(i18n.rowOccupied);
                return;
            }

            page.row_count -= 1;
            page.row_sizes = this.normalizeTrackSizes(page.row_sizes.slice(0, page.row_count), page.row_count);
            await this.persistActivePage();
        },

        // Increase column count and persist immediately.
        async addColumn() {
            const page = this.activePage();
            if (!page) {
                return;
            }
            if (page.column_count >= this.maxTracks) {
                this.flashError(i18n.maxColumnsReached);
                return;
            }

            page.column_count += 1;
            page.column_sizes = this.addTrack(page.column_sizes, page.column_count);
            await this.persistActivePage();
        },

        // Remove last column only when no placement intersects that track.
        async removeColumn() {
            const page = this.activePage();
            if (!page || page.column_count <= 1) {
                return;
            }

            const lastColumn = page.column_count;
            const blocked = page.placements.some((placement) => (placement.column_index + placement.column_span - 1) >= lastColumn);
            if (blocked) {
                this.flashError(i18n.columnOccupied);
                return;
            }

            page.column_count -= 1;
            page.column_sizes = this.normalizeTrackSizes(page.column_sizes.slice(0, page.column_count), page.column_count);
            await this.persistActivePage();
        },

        // Reset row/column percentages to an evenly distributed grid.
        async resetGridSizes() {
            const page = this.activePage();
            if (!page) {
                return;
            }

            page.row_sizes = this.normalizeTrackSizes([], page.row_count);
            page.column_sizes = this.normalizeTrackSizes([], page.column_count);

            await this.persistActivePage();
        },

        // Add one track while preserving relative sizes of existing tracks.
        addTrack(currentSizes, targetCount) {
            if (targetCount <= 0) {
                return [];
            }

            if (!Array.isArray(currentSizes) || currentSizes.length === 0) {
                return this.normalizeTrackSizes([], targetCount);
            }

            const newTrack = Math.max(8, 100 / targetCount);
            const scale = (100 - newTrack) / 100;
            const scaled = currentSizes.map((value) => value * scale);

            return this.normalizeTrackSizes([...scaled, newTrack], targetCount);
        },

        // Build flat row/column coordinates used to render editable grid cells.
        gridCells() {
            const page = this.activePage();
            if (!page) {
                return [];
            }

            const cells = [];
            for (let row = 1; row <= page.row_count; row++) {
                for (let column = 1; column <= page.column_count; column++) {
                    cells.push({ row, column });
                }
            }

            return cells;
        },

        cellStyle(row, column) {
            return `grid-row:${row} / span 1; grid-column:${column} / span 1;`;
        },

        placementStyle(placement) {
            return `grid-row:${placement.row_index} / span ${placement.row_span}; grid-column:${placement.column_index} / span ${placement.column_span};`;
        },

        gridStyle() {
            const page = this.activePage();
            if (!page) {
                return '';
            }

            const rows = page.row_sizes.map((value) => `${value}%`).join(' ');
            const columns = page.column_sizes.map((value) => `${value}%`).join(' ');

            return `grid-template-rows:${rows}; grid-template-columns:${columns};`;
        },

        // Convert cumulative column percentages into draggable boundary positions.
        columnBoundaries() {
            const page = this.activePage();
            if (!page || page.column_sizes.length < 2) {
                return [];
            }

            const boundaries = [];
            let running = 0;
            for (let index = 0; index < page.column_sizes.length - 1; index++) {
                running += page.column_sizes[index];
                boundaries.push(Number(running.toFixed(4)));
            }

            return boundaries;
        },

        // Convert cumulative row percentages into draggable boundary positions.
        rowBoundaries() {
            const page = this.activePage();
            if (!page || page.row_sizes.length < 2) {
                return [];
            }

            const boundaries = [];
            let running = 0;
            for (let index = 0; index < page.row_sizes.length - 1; index++) {
                running += page.row_sizes[index];
                boundaries.push(Number(running.toFixed(4)));
            }

            return boundaries;
        },

        // Begin resize interaction and store baseline geometry for delta calculations.
        startResize(axis, boundaryIndex, event) {
            const page = this.activePage();
            const grid = this.$refs.grid;
            if (!page || !grid) {
                return;
            }

            this.resizeState = {
                axis,
                boundaryIndex,
                startX: event.clientX,
                startY: event.clientY,
                initialRows: [...page.row_sizes],
                initialColumns: [...page.column_sizes],
            };

            window.addEventListener('mousemove', this.boundResizeMove);
            window.addEventListener('mouseup', this.boundResizeEnd);
        },

        // Update adjacent tracks while dragging, enforcing minimum track percentage.
        handleResizeMove(event) {
            if (!this.resizeState) {
                return;
            }

            const page = this.activePage();
            const grid = this.$refs.grid;
            if (!page || !grid) {
                return;
            }

            const minTrack = 6;

            if (this.resizeState.axis === 'column') {
                const totalWidth = Math.max(1, grid.clientWidth);
                const deltaPercent = ((event.clientX - this.resizeState.startX) / totalWidth) * 100;
                const leftIndex = this.resizeState.boundaryIndex;
                const rightIndex = leftIndex + 1;
                const next = [...this.resizeState.initialColumns];

                let leftValue = next[leftIndex] + deltaPercent;
                let rightValue = next[rightIndex] - deltaPercent;

                if (leftValue < minTrack) {
                    rightValue -= (minTrack - leftValue);
                    leftValue = minTrack;
                }
                if (rightValue < minTrack) {
                    leftValue -= (minTrack - rightValue);
                    rightValue = minTrack;
                }

                next[leftIndex] = leftValue;
                next[rightIndex] = rightValue;
                page.column_sizes = this.normalizeTrackSizes(next, page.column_count);
            } else {
                const totalHeight = Math.max(1, grid.clientHeight);
                const deltaPercent = ((event.clientY - this.resizeState.startY) / totalHeight) * 100;
                const topIndex = this.resizeState.boundaryIndex;
                const bottomIndex = topIndex + 1;
                const next = [...this.resizeState.initialRows];

                let topValue = next[topIndex] + deltaPercent;
                let bottomValue = next[bottomIndex] - deltaPercent;

                if (topValue < minTrack) {
                    bottomValue -= (minTrack - topValue);
                    topValue = minTrack;
                }
                if (bottomValue < minTrack) {
                    topValue -= (minTrack - bottomValue);
                    bottomValue = minTrack;
                }

                next[topIndex] = topValue;
                next[bottomIndex] = bottomValue;
                page.row_sizes = this.normalizeTrackSizes(next, page.row_count);
            }
        },

        // Finish resize interaction and persist silently to backend.
        endResize() {
            if (!this.resizeState) {
                return;
            }

            this.resizeState = null;
            window.removeEventListener('mousemove', this.boundResizeMove);
            window.removeEventListener('mouseup', this.boundResizeEnd);
            this.persistActivePage({ silent: true });
        },

        // Mark drag payload as a module catalog item (creates new placement on drop).
        startModuleDrag(module, event) {
            this.dragPayload = {
                type: 'module',
                moduleId: String(module.id),
            };
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'copy';
            }
        },

        // Mark drag payload as an existing placement (move operation on drop).
        startPlacementDrag(placement, event) {
            this.dragPayload = {
                type: 'placement',
                placementId: Number(placement.id),
            };
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
            }
        },

        // Handle drop target for either module creation or placement move.
        async dropOnCell(rowIndex, columnIndex) {
            if (!this.dragPayload) {
                return;
            }

            const page = this.activePage();
            if (!page) {
                this.dragPayload = null;
                return;
            }

            try {
                if (this.dragPayload.type === 'module') {
                    const result = await this.request(this.placementStoreUrl(page.id), 'POST', {
                        module_id: this.dragPayload.moduleId,
                        row_index: rowIndex,
                        column_index: columnIndex,
                        row_span: 1,
                        column_span: 1,
                        order: 0,
                        config_overrides: '{}',
                    });

                    if (result.page) {
                        this.applyPagePatch(result.page);
                    }
                    this.flashSuccess(result.message ?? i18n.moduleAdded);
                }

                if (this.dragPayload.type === 'placement') {
                    const placement = page.placements.find((item) => item.id === this.dragPayload.placementId);
                    if (!placement) {
                        this.dragPayload = null;
                        return;
                    }

                    const result = await this.request(this.placementUpdateUrl(page.id, placement.id), 'PATCH', {
                        module_id: placement.module_id,
                        row_index: rowIndex,
                        column_index: columnIndex,
                        row_span: placement.row_span,
                        column_span: placement.column_span,
                        order: placement.order,
                        config_overrides: JSON.stringify(placement.config_overrides ?? {}),
                    });

                    if (result.page) {
                        this.applyPagePatch(result.page);
                    }
                    this.flashSuccess(result.message ?? i18n.modulePlacementUpdated);
                }
            } catch (error) {
                this.flashError(error.message);
            } finally {
                this.dragPayload = null;
            }
        },

        // Remove a placement from current page.
        async removePlacement(placement) {
            const page = this.activePage();
            if (!page) {
                return;
            }

            const confirmed = await window.appConfirm({
                title: i18n.removeModuleTitle ?? 'Remove Module',
                message: i18n.confirmRemoveModule,
                confirmLabel: i18n.deleteAction ?? 'Delete',
                cancelLabel: i18n.cancelAction ?? 'Cancel',
                confirmVariant: 'danger',
            });

            if (!confirmed) {
                return;
            }

            try {
                const result = await this.request(this.placementDestroyUrl(page.id, placement.id), 'DELETE');
                if (result.page) {
                    this.applyPagePatch(result.page);
                }
                this.flashSuccess(result.message ?? i18n.moduleRemoved);
            } catch (error) {
                this.flashError(error.message);
            }
        },

        // Expand or shrink placement span while keeping it inside page bounds.
        async changePlacementSpan(placement, axis, direction) {
            const page = this.activePage();
            if (!page) {
                return;
            }

            const nextRowSpan = axis === 'row'
                ? Math.max(1, placement.row_span + direction)
                : placement.row_span;
            const nextColumnSpan = axis === 'column'
                ? Math.max(1, placement.column_span + direction)
                : placement.column_span;

            if ((placement.row_index + nextRowSpan - 1) > page.row_count) {
                this.flashError(i18n.rowSpanExceeded);
                return;
            }
            if ((placement.column_index + nextColumnSpan - 1) > page.column_count) {
                this.flashError(i18n.columnSpanExceeded);
                return;
            }

            try {
                const result = await this.request(this.placementUpdateUrl(page.id, placement.id), 'PATCH', {
                    module_id: placement.module_id,
                    row_index: placement.row_index,
                    column_index: placement.column_index,
                    row_span: nextRowSpan,
                    column_span: nextColumnSpan,
                    order: placement.order,
                    config_overrides: JSON.stringify(placement.config_overrides ?? {}),
                });

                if (result.page) {
                    this.applyPagePatch(result.page);
                }
                this.flashSuccess(result.message ?? i18n.modulePlacementUpdated);
            } catch (error) {
                this.flashError(error.message);
            }
        },

        // Persist page metadata + track sizes to API.
        async persistActivePage(options = {}) {
            const page = this.activePage();
            if (!page) {
                return;
            }

            this.isSaving = true;
            try {
                const result = await this.request(this.pageUpdateUrl(page.id), 'PATCH', {
                    name: page.name,
                    order: page.order,
                    duration: page.duration,
                    is_active: page.is_active ? 1 : 0,
                    row_count: page.row_count,
                    column_count: page.column_count,
                    row_sizes: page.row_sizes,
                    column_sizes: page.column_sizes,
                });

                if (result.page) {
                    this.applyPagePatch(result.page);
                }

                if (!options.silent) {
                    this.flashSuccess(result.message ?? i18n.pageUpdated);
                }
            } catch (error) {
                this.flashError(error.message);
            } finally {
                this.isSaving = false;
            }
        },

        // Replace local page snapshot with server canonical payload.
        applyPagePatch(rawPage) {
            const patched = this.normalizePage(rawPage);
            const index = this.pages.findIndex((page) => page.id === patched.id);
            if (index === -1) {
                this.pages.push(patched);
                this.activePageIndex = this.pages.length - 1;
                return;
            }

            this.pages.splice(index, 1, patched);
            this.activePageIndex = index;
        },

        // Resolve route template placeholders for page update endpoint.
        pageUpdateUrl(pageId) {
            return String(this.routes.pageUpdate).replace('__PAGE__', pageId);
        },

        pageDestroyUrl(pageId) {
            return String(this.routes.pageDestroy).replace('__PAGE__', pageId);
        },

        placementStoreUrl(pageId) {
            return String(this.routes.placementStore).replace('__PAGE__', pageId);
        },

        // Resolve route template placeholders for placement update endpoint.
        placementUpdateUrl(pageId, placementId) {
            return String(this.routes.placementUpdate)
                .replace('__PAGE__', pageId)
                .replace('__PLACEMENT__', placementId);
        },

        placementDestroyUrl(pageId, placementId) {
            return String(this.routes.placementDestroy)
                .replace('__PAGE__', pageId)
                .replace('__PLACEMENT__', placementId);
        },

        // Send successful async actions through the shared toast host.
        flashSuccess(message) {
            // Route async layout updates through the shared toast host so the editor matches
            // redirect-based feedback elsewhere in the app.
            window.appToast?.({
                type: 'success',
                message,
            });
        },

        // Send failed async actions through the shared toast host.
        flashError(message) {
            // Route async layout failures through the same light toast system.
            window.appToast?.({
                type: 'error',
                message,
            });
        },

        // Unified JSON request helper with validation-aware error extraction.
        async request(url, method, payload = null) {
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            };

            const options = { method, headers };
            if (payload !== null) {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(payload);
            }

            const response = await fetch(url, options);
            const contentType = response.headers.get('content-type') || '';
            const body = contentType.includes('application/json')
                ? await response.json()
                : {};

            if (!response.ok) {
                const firstValidationError = body?.errors
                    ? Object.values(body.errors)?.[0]?.[0]
                    : null;
                throw new Error(firstValidationError || body?.message || i18n.requestFailed);
            }

            return body;
        },
    };
}

/**
 * Expose factory globally for Blade `x-data="profileLayoutEditor(...)"` usage.
 */
if (typeof window !== 'undefined') {
    window.profileLayoutEditor = profileLayoutEditor;
}
