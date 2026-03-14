import { formatPreviewError, parseJsonResponse, toString } from './helpers';

/**
 * Build the preview-rendering portion of the Alpine state.
 *
 * Preview requests reuse the same hidden payload fields as normal save actions,
 * so guided and raw modes stay in sync.
 *
 * @returns {Object<string, unknown>}
 */
export const createPreviewState = () => ({
    isPreviewLoading: false,

    get isPreviewing() {
        return this.isPreviewLoading;
    },

    hasPreviewHtml(value = this.previewHtml) {
        return typeof value === 'string' && value.trim() !== '';
    },

    hasPreviewError(value = this.previewError) {
        return typeof value === 'string' && value.trim() !== '';
    },

    previewDocumentHtml(value = this.previewHtml) {
        return `${this.previewDocumentPrefix}${typeof value === 'string' ? value : ''}${this.previewDocumentSuffix}`;
    },

    async renderDraftPreview() {
        const form = this.$refs.moduleEditorForm;
        if (!(form instanceof HTMLFormElement) || this.previewRoute.trim() === '') {
            return;
        }

        if (!this.prepareForSubmit()) {
            this.previewError = this.settingsError || 'Preview could not be rendered because the settings payload is invalid.';
            return;
        }

        this.isPreviewLoading = true;
        this.previewError = '';

        try {
            const formData = new FormData(form);
            const csrfToken = toString(formData.get('_token'));
            const response = await fetch(this.previewRoute, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken !== '' ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: formData,
                credentials: 'same-origin',
            });

            const payload = await parseJsonResponse(response);

            if (!response.ok) {
                this.previewError = formatPreviewError(payload, response.status);
                return;
            }

            this.previewHtml = toString(payload.preview_html);
            this.previewError = toString(payload.preview_error);
        } catch {
            this.previewError = 'Preview could not be rendered right now. Please try again.';
        } finally {
            this.isPreviewLoading = false;
        }
    },
});
