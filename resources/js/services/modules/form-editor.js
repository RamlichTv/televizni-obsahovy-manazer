/**
 * @typedef {Object} ModuleFormSettings
 * @property {string} [title]
 * @property {string} [provider]
 * @property {string} [typeName]
 * @property {string} [statusName]
 * @property {string} [orderBy]
 * @property {string} [orderDirection]
 * @property {number|string} [limit]
 */

/**
 * @typedef {Object} ModuleFormInitialState
 * @property {ModuleFormSettings} [settings]
 * @property {Object<string, Object<string, unknown>>} [templatePresets]
 * @property {Object<string, unknown>} [baseConfigSchema]
 * @property {Object<string, unknown>} [baseDefaultConfig]
 * @property {Object<string, unknown>} [baseDataSource]
 */

/**
 * Alpine state factory for the module create/edit form.
 * Keeps form behavior in JS so Blade templates stay focused on structure.
 *
 * @param {ModuleFormInitialState} [initialState={}]
 * @returns {Object<string, unknown>}
 */
export const moduleFormEditor = (initialState = {}) => {
    const settings = toObject(initialState.settings);

    return {
        sidebarOpen: true,
        sidebarTab: 'code',
        codeTab: 'html',
        jsonTab: 'schema',
        createSettings: {
            title: toString(settings.title, 'My Module'),
            provider: toString(settings.provider, 'content_objects'),
            typeName: toString(settings.typeName),
            statusName: toString(settings.statusName, 'Published'),
            orderBy: toString(settings.orderBy, 'published_at'),
            orderDirection: toString(settings.orderDirection, 'desc'),
            limit: clampLimit(settings.limit),
        },
        templatePresets: toObject(initialState.templatePresets),
        selectedTemplatePreset: '',
        baseConfigSchema: toObject(initialState.baseConfigSchema),
        baseDefaultConfig: toObject(initialState.baseDefaultConfig),
        baseDataSource: toObject(initialState.baseDataSource),

        // Updates textareas and notifies CodeMirror wrappers to stay in sync.
        setCodeField(ref, value) {
            if (!ref) {
                return;
            }

            const next = typeof value === 'string' ? value : '';
            ref.value = next;
            ref.dispatchEvent(new CustomEvent('module-code-editor:set-value', {
                bubbles: true,
                detail: { value: next },
            }));
            ref.dispatchEvent(new Event('input', { bubbles: true }));
            ref.dispatchEvent(new Event('change', { bubbles: true }));
        },

        applyTemplatePreset() {
            const preset = this.templatePresets[this.selectedTemplatePreset] ?? null;
            if (!preset) {
                return;
            }

            // Push preset code into the editor fields.
            this.setCodeField(this.$refs.templateHtmlField, preset.template_html ?? '');
            this.setCodeField(this.$refs.templateCssField, preset.template_css ?? '');

            if (this.$refs.templateJsField && typeof preset.template_js === 'string') {
                this.setCodeField(this.$refs.templateJsField, preset.template_js);
            }

            const presetDefaults = toObject(preset.default_config);
            const presetSource = toObject(preset.data_source);

            // Merge defaults/source so manual edits are preserved unless preset overrides them.
            this.baseDefaultConfig = { ...this.baseDefaultConfig, ...presetDefaults };
            this.baseDataSource = { ...this.baseDataSource, ...presetSource };

            if (typeof presetDefaults.title === 'string' && presetDefaults.title.trim() !== '') {
                this.createSettings.title = presetDefaults.title;
            }

            if (typeof presetSource.provider === 'string' && presetSource.provider.trim() !== '') {
                this.createSettings.provider = presetSource.provider;
            }

            const presetFilters = toObject(presetSource.filters);
            this.createSettings.typeName = toString(presetFilters.type_name);
            this.createSettings.statusName = toString(presetFilters.status_name);

            if (typeof presetSource.order_by === 'string' && presetSource.order_by.trim() !== '') {
                this.createSettings.orderBy = presetSource.order_by;
            }

            if (typeof presetSource.order_direction === 'string' && presetSource.order_direction.trim() !== '') {
                this.createSettings.orderDirection = presetSource.order_direction;
            }

            this.createSettings.limit = clampLimit(presetSource.limit);
        },

        buildConfigSchema() {
            // Keep existing schema keys and only enforce/refresh the title definition.
            const base = toObject(this.baseConfigSchema);
            const titleDef = toObject(base.title);

            titleDef.type = titleDef.type || 'string';
            titleDef.default = this.createSettings.title || 'My Module';
            base.title = titleDef;

            return JSON.stringify(base);
        },

        buildDefaultConfig() {
            const base = toObject(this.baseDefaultConfig);
            base.title = this.createSettings.title || 'My Module';

            return JSON.stringify(base);
        },

        buildDataSource() {
            // Build normalized data_source payload that matches request validation rules.
            const base = toObject(this.baseDataSource);
            const filters = toObject(base.filters);

            if (this.createSettings.typeName) {
                filters.type_name = this.createSettings.typeName;
            } else {
                delete filters.type_name;
            }

            if (this.createSettings.statusName) {
                filters.status_name = this.createSettings.statusName;
            } else {
                delete filters.status_name;
            }

            base.provider = this.createSettings.provider || base.provider || 'content_objects';
            base.filters = filters;
            base.order_by = this.createSettings.orderBy || base.order_by || 'published_at';
            base.order_direction = this.createSettings.orderDirection || base.order_direction || 'desc';
            base.limit = clampLimit(this.createSettings.limit);

            return JSON.stringify(base);
        },
    };
};

/**
 * @param {unknown} value
 * @returns {Object<string, unknown>}
 */
const toObject = (value) => {
    // Clone plain objects so Alpine updates do not mutate the shared initial reference.
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        return { ...value };
    }

    return {};
};

/**
 * @param {unknown} value
 * @param {string} [fallback='']
 * @returns {string}
 */
const toString = (value, fallback = '') => {
    if (typeof value === 'string') {
        return value;
    }

    return fallback;
};

/**
 * @param {unknown} value
 * @returns {number}
 */
const clampLimit = (value) => {
    // The UI allows only positive integers for provider row limits.
    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return 1;
    }

    return Math.max(1, Math.floor(numeric));
};
