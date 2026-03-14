import { readEditorValue, toString } from './helpers';
import { updatePayloadChannel } from './payload-state';

/**
 * Raw JSON synchronization helpers.
 *
 * The Raw editors keep exact authored JSON text, while the payload store keeps
 * the last valid parsed object beside that text. Guided and Raw therefore stay
 * synchronized without relying on Alpine `x-model` over hidden CodeMirror
 * textareas.
 */

const payloadRefMap = {
    dataSchema: 'dataSchemaRawField',
    moduleData: 'moduleDataRawField',
    dataSource: 'dataSourceRawField',
};

const payloadInputRefMap = {
    dataSchema: 'dataSchemaInput',
    moduleData: 'moduleDataInput',
    dataSource: 'dataSourceInput',
};

/**
 * @param {unknown} ref
 * @param {string} fallback
 * @returns {string}
 */
const readLiveEditorValue = (ref, fallback) => {
    if (ref && typeof ref === 'object' && '__moduleCodeEditor' in ref) {
        const editor = ref.__moduleCodeEditor;
        if (editor && typeof editor.getValue === 'function') {
            return editor.getValue();
        }
    }

    return readEditorValue(ref, fallback);
};

export const createRawJsonState = () => ({
    /**
     * Resolve one Raw textarea ref.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'} key
     * @returns {HTMLTextAreaElement|undefined}
     */
    rawEditorRef(key) {
        const refName = payloadRefMap[key];

        return refName ? this.$refs[refName] : undefined;
    },

    /**
     * Resolve one hidden submit input ref.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'} key
     * @returns {HTMLInputElement|undefined}
     */
    payloadInputRef(key) {
        const refName = payloadInputRefMap[key];

        return refName ? this.$refs[refName] : undefined;
    },

    /**
     * Read the current payload channel.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'} key
     * @returns {{ text: string, parsed: Object<string, unknown>|null, isValid: boolean, lastOrigin: 'init'|'guided'|'raw' }}
     */
    payloadChannel(key) {
        return this.payloadState[key];
    },

    /**
     * Read the current exact JSON text for one payload.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'} key
     * @returns {string}
     */
    currentPayloadText(key) {
        return toString(this.payloadChannel(key)?.text, '{}');
    },

    /**
     * Read the last valid parsed object for one payload.
     *
     * Invalid raw JSON keeps the last valid parsed snapshot but does not expose
     * it to hydration until the current text becomes valid again.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'} key
     * @returns {Object<string, unknown>|null}
     */
    currentPayloadParsed(key) {
        const channel = this.payloadChannel(key);

        return channel?.isValid ? channel.parsed : null;
    },

    /**
     * Update one payload channel from Guided or Raw edits.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'} key
     * @param {unknown} text
     * @param {'init'|'guided'|'raw'} [origin='raw']
     * @returns {{ text: string, parsed: Object<string, unknown>|null, isValid: boolean, lastOrigin: 'init'|'guided'|'raw' }}
     */
    updatePayloadState(key, text, origin = 'raw') {
        const nextChannel = updatePayloadChannel(this.payloadChannel(key), text, origin);

        this.payloadState = {
            ...this.payloadState,
            [key]: nextChannel,
        };
        this.rawJson = {
            ...this.rawJson,
            [key]: nextChannel.text,
        };

        return nextChannel;
    },

    /**
     * Programmatically set a CodeMirror-backed textarea.
     *
     * @param {HTMLTextAreaElement|undefined} ref
     * @param {unknown} value
     * @param {'init'|'guided'|'raw'} [origin='guided']
     * @returns {void}
     */
    setCodeField(ref, value, origin = 'guided') {
        if (!(ref instanceof HTMLTextAreaElement)) {
            return;
        }

        const next = typeof value === 'string' ? value : '';
        ref.value = next;

        if (ref.__moduleCodeEditor && typeof ref.__moduleCodeEditor.setValue === 'function') {
            ref.__moduleCodeEditor.setValue(next, origin);
        }
    },

    /**
     * Read the current text from the visible Raw editor surface.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'} key
     * @returns {string}
     */
    resolveRawJsonValue(key) {
        const fallback = this.currentPayloadText(key);

        return readLiveEditorValue(this.rawEditorRef(key), fallback);
    },

    /**
     * Refresh the payload store from the current Raw editor values.
     *
     * @returns {void}
     */
    syncRawJsonFromEditors() {
        ['dataSchema', 'moduleData', 'dataSource'].forEach((key) => {
            this.updatePayloadState(key, this.resolveRawJsonValue(key), 'raw');
        });
    },

    /**
     * Keep the visible Raw editor in sync with the payload store.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'} key
     * @param {string} value
     * @param {'init'|'guided'|'raw'} [origin='guided']
     * @returns {void}
     */
    syncVisibleRawEditor(key, value, origin = 'guided') {
        this.setCodeField(this.rawEditorRef(key), value, origin);
    },

    /**
     * Trigger a Guided -> Raw mirror after Alpine finishes the current input tick.
     *
     * @returns {void}
     */
    notifyGuidedSettingsChanged() {
        if (typeof this.$nextTick === 'function') {
            this.$nextTick(() => {
                this.handleGuidedSettingsInput();
            });

            return;
        }

        this.handleGuidedSettingsInput();
    },

    /**
     * Mirror Guided controls into the shared payload store and Raw editors.
     *
     * @returns {void}
     */
    handleGuidedSettingsInput() {
        this.mirrorBuilderStateIntoRawJson();
        this.syncHiddenPayloadFields();
    },

    /**
     * Handle one Raw editor change.
     *
     * Only valid Raw JSON hydrates Guided controls. Invalid JSON still updates
     * the exact text stored for submission and mode switching.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'} changedKey
     * @param {unknown} nextValue
     * @param {'init'|'guided'|'raw'} [origin='raw']
     * @returns {void}
     */
    handleRawEditorChange(changedKey, nextValue, origin = 'raw') {
        const channel = this.updatePayloadState(changedKey, nextValue, origin);

        this.syncHiddenPayloadFields();

        if (origin !== 'raw' || !channel.isValid) {
            return;
        }

        this.hydrateBuilderStateFromRawJson(changedKey);
    },

    /**
     * Keep hidden submit inputs aligned with the current payload store.
     *
     * @returns {void}
     */
    syncHiddenPayloadFields() {
        ['dataSchema', 'moduleData', 'dataSource'].forEach((key) => {
            const input = this.payloadInputRef(key);

            if (input instanceof HTMLInputElement) {
                input.value = this.currentPayloadText(key);
            }
        });
    },

    /**
     * Validate and synchronize settings before save/preview.
     *
     * @returns {boolean}
     */
    prepareForSubmit() {
        if (this.settingsMode === 'raw') {
            this.syncRawJsonFromEditors();
            this.settingsError = '';
            this.syncHiddenPayloadFields();

            return true;
        }

        if (!this.validateBuilderState()) {
            return false;
        }

        this.syncRawJsonFromBuilder();
        this.syncHiddenPayloadFields();

        return true;
    },

    /**
     * Toggle between Guided and Raw without changing the current payload store.
     *
     * @param {'builder'|'raw'} mode
     * @returns {void}
     */
    setSettingsMode(mode) {
        const nextMode = mode === 'raw' ? 'raw' : 'builder';

        if (nextMode === this.settingsMode) {
            return;
        }

        this.settingsMode = nextMode;
        this.refreshCodeEditors();
    },

    /**
     * Rebuild all three payloads from Guided state.
     *
     * @returns {boolean}
     */
    syncRawJsonFromBuilder() {
        const dataSchemaBuild = this.buildDataSchema({ mode: 'permissive' });
        const moduleDataBuild = this.buildModuleData({ mode: 'permissive' });
        const dataSource = this.buildDataSource();

        this.settingsError = '';

        this.updatePayloadState('dataSchema', dataSchemaBuild.json, 'guided');
        this.updatePayloadState('moduleData', moduleDataBuild.json, 'guided');
        this.updatePayloadState('dataSource', dataSource, 'guided');

        this.syncVisibleRawEditor('dataSchema', dataSchemaBuild.json, 'guided');
        this.syncVisibleRawEditor('moduleData', moduleDataBuild.json, 'guided');
        this.syncVisibleRawEditor('dataSource', dataSource, 'guided');
        this.syncHiddenPayloadFields();

        return true;
    },

    /**
     * Backfill only invalid Raw payloads from the current Guided state.
     *
     * This keeps already-valid authored Raw JSON intact while still recovering
     * any invalid payloads on first load.
     *
     * @returns {void}
     */
    backfillInvalidRawPayloadsFromBuilder() {
        const dataSchemaBuild = this.buildDataSchema({ mode: 'permissive' });
        const moduleDataBuild = this.buildModuleData({ mode: 'permissive' });
        const dataSource = this.buildDataSource();

        if (this.payloadChannel('dataSchema')?.isValid !== true) {
            this.updatePayloadState('dataSchema', dataSchemaBuild.json, 'guided');
            this.syncVisibleRawEditor('dataSchema', dataSchemaBuild.json, 'guided');
        }

        if (this.payloadChannel('moduleData')?.isValid !== true) {
            this.updatePayloadState('moduleData', moduleDataBuild.json, 'guided');
            this.syncVisibleRawEditor('moduleData', moduleDataBuild.json, 'guided');
        }

        if (this.payloadChannel('dataSource')?.isValid !== true) {
            this.updatePayloadState('dataSource', dataSource, 'guided');
            this.syncVisibleRawEditor('dataSource', dataSource, 'guided');
        }

        this.syncHiddenPayloadFields();
    },

    /**
     * Hydrate Guided controls from the last valid Raw payloads.
     *
     * @param {'dataSchema'|'moduleData'|'dataSource'|null} [changedKey=null]
     * @returns {boolean}
     */
    syncBuilderFromRawJson(changedKey = null) {
        this.settingsError = '';

        const rawSchema = this.currentPayloadParsed('dataSchema');
        const rawModuleData = this.currentPayloadParsed('moduleData');
        const rawSource = this.currentPayloadParsed('dataSource');

        if (changedKey === 'dataSchema' && rawSchema !== null) {
            this.hydrateSchemaFields(rawSchema, this.baseModuleData, { refresh: false });
        }

        if (changedKey === 'moduleData' && rawModuleData !== null) {
            this.hydrateSchemaFields(this.baseDataSchema, rawModuleData, { refresh: false });
        }

        if (changedKey === null && (rawSchema !== null || rawModuleData !== null)) {
            this.hydrateSchemaFields(
                rawSchema ?? this.baseDataSchema,
                rawModuleData ?? this.baseModuleData,
                { refresh: false },
            );
        }

        if ((changedKey === 'dataSource' || changedKey === null) && rawSource !== null) {
            this.applyProviderSettingsFromDataSource(rawSource);
        }

        return ['dataSchema', 'moduleData', 'dataSource']
            .every((key) => this.payloadChannel(key)?.isValid === true);
    },

    /**
     * Read the current submitted `data_schema` payload.
     *
     * @returns {string}
     */
    submittedDataSchema() {
        return this.currentPayloadText('dataSchema');
    },

    /**
     * Read the current submitted `module_data` payload.
     *
     * @returns {string}
     */
    submittedModuleData() {
        return this.currentPayloadText('moduleData');
    },

    /**
     * Read the current submitted `data_source` payload.
     *
     * @returns {string}
     */
    submittedDataSource() {
        return this.currentPayloadText('dataSource');
    },
});
