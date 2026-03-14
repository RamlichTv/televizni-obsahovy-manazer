/**
 * @typedef {Object} DashboardLayoutEditorConfig
 * @property {Object} [layout] Initial dashboard layout payload from backend.
 * @property {Array<Object>} [modules] Widget module catalog available for drag/drop.
 * @property {{save?: string}} [routes] HTTP endpoints consumed by editor actions.
 * @property {string} [csrfToken] CSRF token attached to PATCH requests.
 * @property {number} [maxTracks] Maximum number of rows/columns allowed.
 * @property {Object<string, string>} [i18n] Runtime labels/messages injected from Blade.
 */

/**
 * Alpine factory used by dashboard edit view.
 *
 * The state is exported for module usage and also attached to `window`
 * so Blade can call it directly inside `x-data`.
 *
 * @param {DashboardLayoutEditorConfig} [config={}]
 * @returns {Record<string, unknown>}
 */
export function dashboardEditor(config = {}) {
    const i18n = {
        moduleLabel: 'Module',
        uncategorized: 'Uncategorized',
        showSidebar: 'Show Sidebar',
        hideSidebar: 'Hide Sidebar',
        maxRowsReached: 'Maximum row count reached.',
        rowOccupied: 'Cannot remove row because a module occupies it.',
        maxColumnsReached: 'Maximum column count reached.',
        columnOccupied: 'Cannot remove column because a module occupies it.',
        cellOccupied: 'Selected grid space is already occupied.',
        spanOutOfBounds: 'Placement cannot expand into occupied or out-of-bounds cells.',
        requestFailed: 'Request failed.',
        layoutSaved: 'Dashboard layout saved.',
        ...(config.i18n ?? {}),
    };

    return {
        i18n,
        layout: config.layout ?? {},
        modules: Array.isArray(config.modules) ? config.modules : [],
        routes: config.routes ?? {},
        csrfToken: config.csrfToken ?? '',
        maxTracks: Number(config.maxTracks ?? 24),
        moduleSearch: '',
        sidebarOpen: true,
        openModuleGroups: {},
        selectedPlacementId: null,
        dragPayload: null,
        isSaving: false,
        viewportWidth: window.innerWidth,
        resizeState: null,
        boundResizeMove: null,
        boundResizeEnd: null,
        onViewportResize: null,

        // Normalize backend payload and initialize runtime listeners.
        init() {
            this.layout = this.normalizeLayout(this.layout);
            this.initializeModuleGroups();
            this.boundResizeMove = (event) => this.handleResizeMove(event);
            this.boundResizeEnd = () => this.endResize();
            this.onViewportResize = () => {
                this.viewportWidth = window.innerWidth;
            };
            window.addEventListener('resize', this.onViewportResize, { passive: true });
        },

        // Clean up listeners when Alpine unmounts the component.
        destroy() {
            this.endResize();
            if (this.onViewportResize) {
                window.removeEventListener('resize', this.onViewportResize);
            }
        },

        // Normalize raw layout object into a predictable shape.
        normalizeLayout(layout) {
            const rowCount = Math.max(1, Number(layout.row_count ?? 2));
            const columnCount = Math.max(1, Number(layout.column_count ?? 2));

            return {
                row_count: rowCount,
                column_count: columnCount,
                row_sizes: this.normalizeTrackSizes(layout.row_sizes ?? [], rowCount),
                column_sizes: this.normalizeTrackSizes(layout.column_sizes ?? [], columnCount),
                placements: this.normalizePlacements(layout.placements ?? [], rowCount, columnCount),
            };
        },

        // Keep placement coordinates and ids consistent across responses and local mutations.
        normalizePlacements(placements, rowCount, columnCount) {
            const normalized = [];
            const ids = new Set();
            let nextId = 1;

            for (const placement of placements) {
                if (!placement || typeof placement !== 'object') {
                    continue;
                }

                const moduleId = String(placement.module_id ?? '').trim();
                if (moduleId === '') {
                    continue;
                }

                const module = this.modules.find((candidate) => String(candidate.id) === moduleId);
                const rowIndex = Math.min(rowCount, Math.max(1, Number(placement.row_index ?? 1)));
                const columnIndex = Math.min(columnCount, Math.max(1, Number(placement.column_index ?? 1)));

                let rowSpan = Math.max(1, Number(placement.row_span ?? 1));
                let columnSpan = Math.max(1, Number(placement.column_span ?? 1));
                rowSpan = Math.min(rowSpan, rowCount - rowIndex + 1);
                columnSpan = Math.min(columnSpan, columnCount - columnIndex + 1);

                let id = Math.max(1, Number(placement.id ?? nextId));
                while (ids.has(id)) {
                    id += 1;
                }
                ids.add(id);
                nextId = id + 1;

                const candidate = {
                    id,
                    module_id: moduleId,
                    module_name: String(placement.module_name ?? module?.name ?? i18n.moduleLabel),
                    module_category: String(placement.module_category ?? module?.category ?? ''),
                    row_index: rowIndex,
                    column_index: columnIndex,
                    row_span: rowSpan,
                    column_span: columnSpan,
                    order: Math.max(0, Number(placement.order ?? 0)),
                    config_overrides: placement.config_overrides && typeof placement.config_overrides === 'object'
                        ? placement.config_overrides
                        : {},
                    rendered_html: String(placement.rendered_html ?? ''),
                };

                if (this.hasOverlap(candidate, normalized)) {
                    continue;
                }

                normalized.push(candidate);
            }

            return normalized;
        },

        // Normalize track percentages so each axis always sums to 100.
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

            const total = clean.reduce((sum, value) => sum + value, 0);
            if (total <= 0) {
                return Array.from({ length: count }, () => Number((100 / count).toFixed(4)));
            }

            return clean.map((value) => Number(((value / total) * 100).toFixed(4)));
        },

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

        initializeModuleGroups() {
            for (const module of this.modules) {
                const key = this.moduleCategoryKey(module.category);
                if (!(key in this.openModuleGroups)) {
                    this.openModuleGroups[key] = true;
                }
            }
        },

        moduleCategoryKey(category) {
            const value = String(category ?? '').trim();
            return value === '' ? '__uncategorized__' : value;
        },

        groupedModules() {
            const groups = new Map();

            for (const module of this.filteredModules()) {
                const rawCategory = String(module.category ?? '').trim();
                const key = this.moduleCategoryKey(rawCategory);
                const label = rawCategory === '' ? i18n.uncategorized : rawCategory;

                if (!(key in this.openModuleGroups)) {
                    this.openModuleGroups[key] = true;
                }

                if (!groups.has(key)) {
                    groups.set(key, {
                        key,
                        label,
                        modules: [],
                    });
                }

                groups.get(key).modules.push(module);
            }

            return Array.from(groups.values()).sort((first, second) => {
                if (first.key === '__uncategorized__') {
                    return 1;
                }
                if (second.key === '__uncategorized__') {
                    return -1;
                }

                return first.label.localeCompare(second.label);
            });
        },

        isModuleGroupOpen(groupKey) {
            if (this.moduleSearch.trim() !== '') {
                return true;
            }

            return this.openModuleGroups[groupKey] ?? true;
        },

        toggleModuleGroup(groupKey) {
            if (this.moduleSearch.trim() !== '') {
                return;
            }

            this.openModuleGroups[groupKey] = !this.isModuleGroupOpen(groupKey);
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

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

        addRow() {
            if (this.layout.row_count >= this.maxTracks) {
                this.flashError(i18n.maxRowsReached);
                return;
            }

            this.layout.row_count += 1;
            this.layout.row_sizes = this.addTrack(this.layout.row_sizes, this.layout.row_count);
        },

        removeRow() {
            if (this.layout.row_count <= 1) {
                return;
            }

            const lastRow = this.layout.row_count;
            const blocked = this.layout.placements.some((placement) => (placement.row_index + placement.row_span - 1) >= lastRow);
            if (blocked) {
                this.flashError(i18n.rowOccupied);
                return;
            }

            this.layout.row_count -= 1;
            this.layout.row_sizes = this.normalizeTrackSizes(this.layout.row_sizes.slice(0, this.layout.row_count), this.layout.row_count);
        },

        addColumn() {
            if (this.layout.column_count >= this.maxTracks) {
                this.flashError(i18n.maxColumnsReached);
                return;
            }

            this.layout.column_count += 1;
            this.layout.column_sizes = this.addTrack(this.layout.column_sizes, this.layout.column_count);
        },

        removeColumn() {
            if (this.layout.column_count <= 1) {
                return;
            }

            const lastColumn = this.layout.column_count;
            const blocked = this.layout.placements.some((placement) => (placement.column_index + placement.column_span - 1) >= lastColumn);
            if (blocked) {
                this.flashError(i18n.columnOccupied);
                return;
            }

            this.layout.column_count -= 1;
            this.layout.column_sizes = this.normalizeTrackSizes(this.layout.column_sizes.slice(0, this.layout.column_count), this.layout.column_count);
        },

        gridCells() {
            const cells = [];
            for (let row = 1; row <= this.layout.row_count; row++) {
                for (let column = 1; column <= this.layout.column_count; column++) {
                    cells.push({ row, column });
                }
            }

            return cells;
        },

        // Calculate responsive canvas dimensions while preserving scrollable large grids.
        canvasStyle() {
            const baseColumn = this.viewportWidth < 640 ? 120 : this.viewportWidth < 1024 ? 160 : 210;
            const baseRow = this.viewportWidth < 640 ? 96 : this.viewportWidth < 1024 ? 128 : 170;
            const minWidthFloor = this.viewportWidth < 640 ? 320 : this.viewportWidth < 1024 ? 600 : 900;
            const minHeightFloor = this.viewportWidth < 640 ? 360 : this.viewportWidth < 1024 ? 480 : 640;
            const minWidth = Math.max(minWidthFloor, this.layout.column_count * baseColumn);
            const minHeight = Math.max(minHeightFloor, this.layout.row_count * baseRow);

            return `width:max(100%, ${minWidth}px); height:max(100%, ${minHeight}px);`;
        },

        gridStyle() {
            const rows = this.layout.row_sizes.map((value) => `${value}%`).join(' ');
            const columns = this.layout.column_sizes.map((value) => `${value}%`).join(' ');

            return `grid-template-rows:${rows}; grid-template-columns:${columns};`;
        },

        columnBoundaries() {
            if (this.layout.column_sizes.length < 2) {
                return [];
            }

            const boundaries = [];
            let running = 0;
            for (let index = 0; index < this.layout.column_sizes.length - 1; index++) {
                running += this.layout.column_sizes[index];
                boundaries.push(Number(running.toFixed(4)));
            }

            return boundaries;
        },

        rowBoundaries() {
            if (this.layout.row_sizes.length < 2) {
                return [];
            }

            const boundaries = [];
            let running = 0;
            for (let index = 0; index < this.layout.row_sizes.length - 1; index++) {
                running += this.layout.row_sizes[index];
                boundaries.push(Number(running.toFixed(4)));
            }

            return boundaries;
        },

        // Start drag-resize interaction for row/column boundaries.
        startResize(axis, boundaryIndex, event) {
            const grid = this.$refs.grid;
            if (!grid) {
                return;
            }

            this.resizeState = {
                axis,
                boundaryIndex,
                startX: event.clientX,
                startY: event.clientY,
                initialRows: [...this.layout.row_sizes],
                initialColumns: [...this.layout.column_sizes],
            };

            window.addEventListener('pointermove', this.boundResizeMove);
            window.addEventListener('pointerup', this.boundResizeEnd);
            window.addEventListener('pointercancel', this.boundResizeEnd);
        },

        // Apply resize deltas while enforcing a minimum track size.
        handleResizeMove(event) {
            if (!this.resizeState) {
                return;
            }

            const grid = this.$refs.grid;
            if (!grid) {
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
                this.layout.column_sizes = this.normalizeTrackSizes(next, this.layout.column_count);
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
                this.layout.row_sizes = this.normalizeTrackSizes(next, this.layout.row_count);
            }
        },

        endResize() {
            if (!this.resizeState) {
                return;
            }

            this.resizeState = null;
            window.removeEventListener('pointermove', this.boundResizeMove);
            window.removeEventListener('pointerup', this.boundResizeEnd);
            window.removeEventListener('pointercancel', this.boundResizeEnd);
        },

        // Mount module preview HTML and execute inline scripts once content changes.
        mountModulePreview(element, placement) {
            if (!element || !placement || typeof placement !== 'object') {
                return;
            }

            const html = String(placement.rendered_html ?? '');
            const key = `${placement.id ?? 'new'}:${html.length}:${html.slice(0, 24)}:${html.slice(-24)}`;

            if (element.dataset.renderKey === key) {
                return;
            }

            element.dataset.renderKey = key;
            element.innerHTML = html;
            this.activateInlineScripts(element);
        },

        activateInlineScripts(element) {
            const scripts = Array.from(element.querySelectorAll('script'));
            for (const original of scripts) {
                const replacement = document.createElement('script');
                for (const attribute of Array.from(original.attributes)) {
                    replacement.setAttribute(attribute.name, attribute.value);
                }
                replacement.textContent = original.textContent ?? '';
                original.replaceWith(replacement);
            }
        },

        cellStyle(row, column) {
            return `grid-row:${row} / span 1; grid-column:${column} / span 1;`;
        },

        isCellCovered(rowIndex, columnIndex) {
            return this.layout.placements.some((placement) => {
                const rowEnd = placement.row_index + placement.row_span - 1;
                const columnEnd = placement.column_index + placement.column_span - 1;

                return rowIndex >= placement.row_index
                    && rowIndex <= rowEnd
                    && columnIndex >= placement.column_index
                    && columnIndex <= columnEnd;
            });
        },

        placementStyle(placement) {
            return `grid-row:${placement.row_index} / span ${placement.row_span}; grid-column:${placement.column_index} / span ${placement.column_span};`;
        },

        startModuleDrag(module, event) {
            this.dragPayload = {
                type: 'module',
                moduleId: String(module.id),
            };

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'copy';
            }
        },

        startPlacementDrag(placement, event) {
            this.dragPayload = {
                type: 'placement',
                placementId: Number(placement.id),
            };

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
            }
        },

        dropOnCell(rowIndex, columnIndex) {
            if (!this.dragPayload) {
                return;
            }

            if (this.dragPayload.type === 'module') {
                const module = this.modules.find((candidate) => String(candidate.id) === String(this.dragPayload.moduleId));
                if (!module) {
                    this.dragPayload = null;
                    return;
                }

                const candidate = {
                    id: Date.now() + Math.floor(Math.random() * 1000),
                    module_id: String(module.id),
                    module_name: String(module.name ?? i18n.moduleLabel),
                    module_category: String(module.category ?? ''),
                    row_index: rowIndex,
                    column_index: columnIndex,
                    row_span: 1,
                    column_span: 1,
                    order: 0,
                    config_overrides: {},
                    rendered_html: String(module.rendered_html ?? ''),
                };

                if (!this.canPlace(candidate.row_index, candidate.column_index, candidate.row_span, candidate.column_span)) {
                    this.flashError(i18n.cellOccupied);
                    this.dragPayload = null;
                    return;
                }

                this.layout.placements.push(candidate);
                this.selectedPlacementId = candidate.id;
            }

            if (this.dragPayload.type === 'placement') {
                const placement = this.layout.placements.find((candidate) => candidate.id === this.dragPayload.placementId);
                if (!placement) {
                    this.dragPayload = null;
                    return;
                }

                if (!this.canPlace(rowIndex, columnIndex, placement.row_span, placement.column_span, placement.id)) {
                    this.flashError(i18n.cellOccupied);
                    this.dragPayload = null;
                    return;
                }

                placement.row_index = rowIndex;
                placement.column_index = columnIndex;
                this.selectedPlacementId = placement.id;
            }

            this.dragPayload = null;
        },

        changePlacementSpan(placement, axis, direction) {
            const nextRowSpan = axis === 'row'
                ? Math.max(1, placement.row_span + direction)
                : placement.row_span;
            const nextColumnSpan = axis === 'column'
                ? Math.max(1, placement.column_span + direction)
                : placement.column_span;

            if (!this.canPlace(
                placement.row_index,
                placement.column_index,
                nextRowSpan,
                nextColumnSpan,
                placement.id
            )) {
                this.flashError(i18n.spanOutOfBounds);
                return;
            }

            placement.row_span = nextRowSpan;
            placement.column_span = nextColumnSpan;
        },

        removePlacement(placement) {
            this.layout.placements = this.layout.placements.filter((candidate) => candidate.id !== placement.id);
            if (this.selectedPlacementId === placement.id) {
                this.selectedPlacementId = null;
            }
        },

        canPlace(rowIndex, columnIndex, rowSpan = 1, columnSpan = 1, ignoreId = null) {
            const candidate = {
                row_index: rowIndex,
                column_index: columnIndex,
                row_span: rowSpan,
                column_span: columnSpan,
            };

            const maxRow = rowIndex + rowSpan - 1;
            const maxColumn = columnIndex + columnSpan - 1;
            if (rowIndex < 1 || columnIndex < 1 || maxRow > this.layout.row_count || maxColumn > this.layout.column_count) {
                return false;
            }

            for (const placement of this.layout.placements) {
                if (ignoreId !== null && Number(placement.id) === Number(ignoreId)) {
                    continue;
                }

                if (this.placementsOverlap(candidate, placement)) {
                    return false;
                }
            }

            return true;
        },

        placementsOverlap(first, second) {
            const firstRowStart = Number(first.row_index);
            const firstRowEnd = firstRowStart + Number(first.row_span) - 1;
            const firstColumnStart = Number(first.column_index);
            const firstColumnEnd = firstColumnStart + Number(first.column_span) - 1;

            const secondRowStart = Number(second.row_index);
            const secondRowEnd = secondRowStart + Number(second.row_span) - 1;
            const secondColumnStart = Number(second.column_index);
            const secondColumnEnd = secondColumnStart + Number(second.column_span) - 1;

            if (firstRowEnd < secondRowStart || secondRowEnd < firstRowStart) {
                return false;
            }
            if (firstColumnEnd < secondColumnStart || secondColumnEnd < firstColumnStart) {
                return false;
            }

            return true;
        },

        hasOverlap(candidate, placements) {
            return placements.some((placement) => this.placementsOverlap(candidate, placement));
        },

        exportLayout() {
            return {
                row_count: this.layout.row_count,
                column_count: this.layout.column_count,
                row_sizes: this.layout.row_sizes,
                column_sizes: this.layout.column_sizes,
                placements: this.layout.placements.map((placement) => ({
                    id: placement.id,
                    module_id: placement.module_id,
                    row_index: placement.row_index,
                    column_index: placement.column_index,
                    row_span: placement.row_span,
                    column_span: placement.column_span,
                    order: placement.order,
                    config_overrides: placement.config_overrides ?? {},
                })),
            };
        },

        async saveLayout() {
            this.isSaving = true;

            try {
                const response = await fetch(String(this.routes.save || ''), {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        layout: this.exportLayout(),
                    }),
                });

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

                if (body.layout) {
                    this.layout = this.normalizeLayout(body.layout);
                }

                this.flashSuccess(body.message || i18n.layoutSaved);
            } catch (error) {
                this.flashError(error?.message || i18n.requestFailed);
            } finally {
                this.isSaving = false;
            }
        },

        flashSuccess(message) {
            // Route async save feedback through the shared toast host so editor and full-page
            // actions use the same notification pattern.
            window.appToast?.({
                type: 'success',
                message,
            });
        },

        flashError(message) {
            // Keep runtime errors in the same light toast system used across the app shell.
            window.appToast?.({
                type: 'error',
                message,
            });
        },
    };
}

if (typeof window !== 'undefined') {
    window.dashboardEditor = dashboardEditor;
}
