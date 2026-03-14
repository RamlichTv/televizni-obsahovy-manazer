import { toObject } from './helpers';

/**
 * Preset application helpers for the code pane.
 */
export const createPresetState = () => ({
    applyTemplatePreset() {
        const preset = this.templatePresets[this.selectedTemplatePreset] ?? null;
        if (!preset) {
            return;
        }

        this.setCodeField(this.$refs.templateHtmlField, preset.template_html ?? '');
        this.setCodeField(this.$refs.templateCssField, preset.template_css ?? '');

        if (this.$refs.templateJsField) {
            this.setCodeField(
                this.$refs.templateJsField,
                typeof preset.template_js === 'string' ? preset.template_js : '',
            );
        }

        const presetSchema = toObject(preset.data_schema);
        const presetModuleData = toObject(preset.module_data);
        const presetSource = toObject(preset.data_source);

        this.resetSchemaFields(presetSchema, presetModuleData);
        this.applyProviderSettingsFromDataSource(presetSource);
        this.syncRawJsonFromBuilder();
        this.previewError = '';
    },

    /**
     * Clear authored template code and reset module settings back to an empty baseline.
     *
     * The action intentionally leaves overview fields like module name and category
     * untouched, because the user only asked to clear code and settings.
     */
    clearCodeAndSettings() {
        this.selectedTemplatePreset = '';

        this.setCodeField(this.$refs.templateHtmlField, '');
        this.setCodeField(this.$refs.templateCssField, '');

        if (this.$refs.templateJsField) {
            this.setCodeField(this.$refs.templateJsField, '');
        }

        this.resetSchemaFields({}, {}, { refresh: false });
        this.clearProviderSettings();
        this.settingsError = '';
        this.previewHtml = '';
        this.previewError = '';

        this.syncRawJsonFromBuilder();
        this.refreshCodeEditors();
    },
});
