/**
 * Small helpers for controlling the currently active code editor tab.
 *
 * The module editor only ever exposes one visible code pane at a time, so the
 * sidebar buttons can target the active CodeMirror bridge instead of trying to
 * manage history per field manually.
 */

const codeEditorRefMap = {
    html: 'templateHtmlField',
    css: 'templateCssField',
    js: 'templateJsField',
};

/**
 * Build the active-code-editor control portion of the Alpine state.
 *
 * @returns {Object<string, unknown>}
 */
export const createCodeEditorState = () => ({
    /**
     * Resolve the textarea for the currently selected code tab.
     *
     * @returns {HTMLTextAreaElement|undefined}
     */
    activeCodeEditorRef() {
        const refName = codeEditorRefMap[this.activeTab];

        return refName ? this.$refs[refName] : undefined;
    },

    /**
     * Resolve the imperative CodeMirror bridge for the active tab.
     *
     * @returns {{ undo?: Function, redo?: Function, focus?: Function }|null}
     */
    activeCodeEditorBridge() {
        const ref = this.activeCodeEditorRef();

        if (!(ref instanceof HTMLTextAreaElement) || typeof ref.__moduleCodeEditor !== 'object') {
            return null;
        }

        return ref.__moduleCodeEditor;
    },

    /**
     * Whether the current tab has a live CodeMirror bridge attached yet.
     *
     * @returns {boolean}
     */
    canControlActiveCodeEditor() {
        return this.activeCodeEditorBridge() !== null;
    },

    /**
     * Undo the latest change in the active code tab.
     *
     * @returns {void}
     */
    undoActiveCodeEditor() {
        const editor = this.activeCodeEditorBridge();

        if (editor && typeof editor.undo === 'function') {
            editor.undo();

            if (typeof editor.focus === 'function') {
                editor.focus();
            }
        }
    },

    /**
     * Redo the latest undone change in the active code tab.
     *
     * @returns {void}
     */
    redoActiveCodeEditor() {
        const editor = this.activeCodeEditorBridge();

        if (editor && typeof editor.redo === 'function') {
            editor.redo();

            if (typeof editor.focus === 'function') {
                editor.focus();
            }
        }
    },
});
