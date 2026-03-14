import { createCodeEditorState } from './editor/code-editor-state';
import { createConfigState } from './editor/config-state';
import { toString } from './editor/helpers';
import { createLayoutState } from './editor/layout-state';
import { createPreviewState } from './editor/preview-state';

/**
 * @typedef {Object} ModuleProviderSettings
 * @property {string} [provider]
 * @property {string} [typeName]
 * @property {string} [statusName]
 * @property {'all'|'viewer'|string} [authoredBy]
 * @property {Array<string>} [typeNames]
 * @property {Array<string>} [statusNames]
 * @property {Array<number|string>} [tagIds]
 * @property {string} [orderBy]
 * @property {string} [orderDirection]
 * @property {number|string} [limit]
 */

/**
 * @typedef {Object} ModuleFormInitialState
 * @property {ModuleProviderSettings} [providerSettings]
 * @property {ModuleProviderSettings} [settings]
 * @property {Object<string, Object<string, unknown>>} [templatePresets]
 * @property {Object<string, unknown>} [baseDataSchema]
 * @property {Object<string, unknown>} [baseModuleData]
 * @property {Object<string, unknown>} [baseDataSource]
 * @property {Object<string, string>} [rawJson]
 * @property {'builder'|'raw'} [settingsMode]
 * @property {string} [previewHtml]
 * @property {string} [previewError]
 * @property {string} [previewRoute]
 * @property {string} [previewDocumentPrefix]
 * @property {string} [previewDocumentSuffix]
 * @property {'vertical'|'horizontal'|'preview'} [layout]
 * @property {'code'|'settings'|'meta'} [sidebarTab]
 * @property {'html'|'css'|'js'} [activeTab]
 * @property {number|string} [splitPosition]
 */

/**
 * Alpine state factory for the module editor shell.
 * It composes smaller layout, settings, and preview modules to keep the entrypoint readable.
 *
 * @param {ModuleFormInitialState} [initialState={}]
 * @returns {Object<string, unknown>}
 */
export const moduleEditor = (initialState = {}) => ({
    previewHtml: toString(initialState.previewHtml),
    previewError: toString(initialState.previewError),
    previewRoute: toString(initialState.previewRoute),
    previewDocumentPrefix: toString(initialState.previewDocumentPrefix),
    previewDocumentSuffix: toString(initialState.previewDocumentSuffix),
    ...createLayoutState(initialState),
    ...createCodeEditorState(),
    ...createConfigState(initialState),
    ...createPreviewState(),

    init() {
        this.registerSettingsSyncWatchers();

        // Prefer valid authored raw JSON on load, then only backfill the Raw
        // editors from Guided state when one of the payloads starts invalid.
        this.runSettingsSync('init', () => {
            const rawPayloadsAreValid = this.syncBuilderFromRawJson();

            if (!rawPayloadsAreValid) {
                this.backfillInvalidRawPayloadsFromBuilder();
                return;
            }

            this.syncHiddenPayloadFields();
        });
    },
});

/**
 * Temporary alias so older bindings keep working during rollout.
 */
export const moduleFormEditor = moduleEditor;
