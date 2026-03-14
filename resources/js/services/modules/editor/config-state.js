import {
    clampLimit,
    normalizeIntegerArray,
    normalizeStringArray,
    toObject,
    toString,
    toStringArray,
} from './helpers';
import {
    buildDataSchemaJson,
    buildDataSourceJson,
    buildModuleDataJson,
} from './config-builders';
import { buildInitialPayloadState } from './payload-state';
import { createPresetState } from './preset-state';
import { createRawJsonState } from './raw-json-state';
import { createSchemaState } from './schema-state';
import { createSettingsSyncState } from './settings-sync-state';

/**
 * Build the module-settings portion of the Alpine state.
 *
 * Provider controls, schema rows, preset application, and raw JSON sync all
 * live here so preview/save always read from one authoritative state object.
 *
 * @param {import('../form-editor').ModuleFormInitialState} initialState
 * @returns {Object<string, unknown>}
 */
export const createConfigState = (initialState) => {
    const providerSettings = toObject(initialState.providerSettings ?? initialState.settings);
    const rawJson = toObject(initialState.rawJson);

    return {
        settingsMode: initialState.settingsMode === 'raw' ? 'raw' : 'builder',
        providerSettings: {
            provider: toString(providerSettings.provider, 'content_objects'),
            authoredBy: normalizeAuthoredByScope(providerSettings.authoredBy),
            typeNames: normalizeStringArray([
                ...toStringArray(providerSettings.typeNames),
                toString(providerSettings.typeName),
            ]),
            statusNames: normalizeStringArray([
                ...toStringArray(providerSettings.statusNames),
                toString(providerSettings.statusName, 'Published'),
            ]),
            tagIds: normalizeIntegerArray(providerSettings.tagIds),
            orderBy: toString(providerSettings.orderBy, 'published_at'),
            orderDirection: toString(providerSettings.orderDirection, 'desc'),
            limit: clampLimit(providerSettings.limit ?? 8),
        },
        templatePresets: toObject(initialState.templatePresets),
        selectedTemplatePreset: '',
        baseDataSource: toObject(initialState.baseDataSource),
        rawJson: {
            dataSchema: toString(rawJson.dataSchema, '{}'),
            moduleData: toString(rawJson.moduleData, '{}'),
            dataSource: toString(rawJson.dataSource, '{}'),
        },
        payloadState: buildInitialPayloadState(rawJson),
        ...createSchemaState(initialState),
        ...createPresetState(),
        ...createRawJsonState(),
        ...createSettingsSyncState(),

        applyProviderSettingsFromDataSource(rawSource = {}) {
            const source = toObject(rawSource);
            const rawFilters = toObject(source.filters);

            this.baseDataSource = source;
            this.providerSettings.provider = toString(source.provider, 'content_objects');
            this.providerSettings.authoredBy = normalizeAuthoredByScope(rawFilters.authored_by);
            this.providerSettings.typeNames = normalizeStringArray([
                ...toStringArray(rawFilters.type_names),
                toString(rawFilters.type_name),
            ]);
            this.providerSettings.statusNames = normalizeStringArray([
                ...toStringArray(rawFilters.status_names),
                toString(rawFilters.status_name),
            ]);
            this.providerSettings.tagIds = normalizeIntegerArray(rawFilters.tag_ids);
            this.providerSettings.orderBy = toString(source.order_by, 'published_at');
            this.providerSettings.orderDirection = toString(source.order_direction, 'desc');
            this.providerSettings.limit = clampLimit(source.limit ?? 8);

            if (this.providerSettings.statusNames.length === 0) {
                this.providerSettings.statusNames = ['Published'];
            }
        },

        clearProviderSettings() {
            this.baseDataSource = {};
            this.providerSettings.provider = 'content_objects';
            this.providerSettings.authoredBy = 'all';
            this.providerSettings.typeNames = [];
            this.providerSettings.statusNames = [];
            this.providerSettings.tagIds = [];
            this.providerSettings.orderBy = 'published_at';
            this.providerSettings.orderDirection = 'desc';
            this.providerSettings.limit = 8;
        },

        buildDataSchema(options = {}) {
            return buildDataSchemaJson(this.schemaFields, options);
        },

        buildModuleData(options = {}) {
            return buildModuleDataJson(this.schemaFields, options);
        },

        buildDataSource() {
            return buildDataSourceJson(this.baseDataSource, this.providerSettings);
        },
    };
};

/**
 * Normalize guided author-scope values to the small set the UI understands.
 *
 * @param {unknown} value
 * @returns {'all'|'viewer'}
 */
const normalizeAuthoredByScope = (value) => {
    const normalized = toString(value).trim().toLowerCase();

    return normalized === 'viewer' || normalized === 'current_user'
        ? 'viewer'
        : 'all';
};
