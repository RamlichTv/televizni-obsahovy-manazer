import { toObject } from './helpers';
import {
    buildDataSchemaJson,
    buildModuleDataJson,
} from './config-builders';
import {
    coerceSchemaFieldType,
    createEmptySchemaField,
    mergeSchemaFieldsFromData,
    schemaFieldTypes,
    schemaFieldsFromData,
} from './schema-fields';

/**
 * Build the guided schema/module-data portion of the Alpine state.
 *
 * The same `schemaFields` collection powers both the Schema and Data tabs so
 * the default values always stay aligned with the declared types.
 *
 * @param {import('../form-editor').ModuleFormInitialState} initialState
 * @returns {Object<string, unknown>}
 */
export const createSchemaState = (initialState) => {
    const baseDataSchema = toObject(initialState.baseDataSchema);
    const baseModuleData = toObject(initialState.baseModuleData);

    return {
        moduleSettingsTab: 'schema',
        settingsError: '',
        schemaFieldTypes,
        baseDataSchema,
        baseModuleData,
        schemaFields: schemaFieldsFromData(baseDataSchema, baseModuleData),

        setModuleSettingsTab(nextTab) {
            this.moduleSettingsTab = nextTab === 'data' ? 'data' : 'schema';
            this.refreshCodeEditors();
        },

        moduleSettingsTabClass(tab) {
            return this.moduleSettingsTab === tab
                ? 'bg-[#077CB6] text-white shadow-sm'
                : 'text-gray-600 hover:bg-white hover:text-gray-900';
        },

        addSchemaField() {
            // New fields belong at the top so the author stays near the latest addition.
            this.schemaFields = [createEmptySchemaField(), ...this.schemaFields];
            this.moduleSettingsTab = 'schema';
            this.settingsError = '';
            this.notifyGuidedSettingsChanged();
            this.refreshCodeEditors();
        },

        removeSchemaField(index) {
            this.schemaFields = this.schemaFields.filter((_, fieldIndex) => fieldIndex !== index);
            this.settingsError = '';
            this.notifyGuidedSettingsChanged();
            this.refreshCodeEditors();
        },

        setSchemaFieldType(index, nextType) {
            this.schemaFields = this.schemaFields.map((field, fieldIndex) => {
                if (fieldIndex !== index) {
                    return field;
                }

                return coerceSchemaFieldType(field, nextType);
            });
            this.settingsError = '';
            this.notifyGuidedSettingsChanged();
            this.refreshCodeEditors();
        },

        resetSchemaFields(dataSchema = {}, moduleData = {}, options = {}) {
            this.baseDataSchema = toObject(dataSchema);
            this.baseModuleData = toObject(moduleData);
            this.schemaFields = schemaFieldsFromData(this.baseDataSchema, this.baseModuleData);
            this.settingsError = '';

            if (options.refresh === false) {
                return;
            }

            this.refreshCodeEditors();
        },

        hydrateSchemaFields(dataSchema = this.baseDataSchema, moduleData = this.baseModuleData, options = {}) {
            this.baseDataSchema = toObject(dataSchema);
            this.baseModuleData = toObject(moduleData);
            this.schemaFields = mergeSchemaFieldsFromData(
                this.schemaFields,
                this.baseDataSchema,
                this.baseModuleData,
            );
            this.settingsError = '';

            if (options.refresh === false) {
                return;
            }

            this.refreshCodeEditors();
        },

        refreshCodeEditors() {
            if (typeof this.$nextTick !== 'function') {
                window.dispatchEvent(new CustomEvent('module-code-editor:refresh'));
                return;
            }

            this.$nextTick(() => {
                window.dispatchEvent(new CustomEvent('module-code-editor:refresh'));
            });
        },

        validateBuilderState() {
            const schemaBuild = buildDataSchemaJson(this.schemaFields, { mode: 'strict' });
            const moduleDataBuild = buildModuleDataJson(this.schemaFields, { mode: 'strict' });
            const errors = [...new Set([...schemaBuild.errors, ...moduleDataBuild.errors])];

            this.settingsError = errors[0] ?? '';

            return errors.length === 0;
        },
    };
};
