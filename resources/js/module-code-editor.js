import { EditorState } from '@codemirror/state';
import { EditorView, keymap } from '@codemirror/view';
import { indentWithTab, redo, undo } from '@codemirror/commands';
import { basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { javascript } from '@codemirror/lang-javascript';
import { json } from '@codemirror/lang-json';

/**
 * Resolve the CodeMirror language pack requested by the textarea.
 *
 * @param {string} language
 * @returns {import('@codemirror/state').Extension}
 */
function languageExtension(language) {
    switch (language) {
        case 'html':
            return html();
        case 'css':
            return css();
        case 'javascript':
        case 'js':
            return javascript();
        case 'json':
            return json();
        default:
            return html();
    }
}

/**
 * Keep the hidden textarea in sync with editor changes so normal HTML form submission still works.
 *
 * @param {HTMLTextAreaElement} textarea
 */
function syncTextareaEvents(textarea) {
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    textarea.dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * Upgrade one textarea into a CodeMirror 6 instance.
 * The optional `data-editor-fill="1"` flag makes the editor stretch to the full height of its pane.
 *
 * @param {Element} textarea
 */
function setupModuleCodeEditor(textarea) {
    if (!(textarea instanceof HTMLTextAreaElement) || textarea.dataset.editorReady === '1') {
        return;
    }

    const language = (textarea.dataset.editorLanguage || 'html').toLowerCase();
    const isReadOnly = textarea.dataset.editorReadonly === '1'
        || textarea.hasAttribute('readonly')
        || textarea.hasAttribute('disabled');
    const fillMode = textarea.dataset.editorFill === '1';
    const rows = Number.parseInt(textarea.getAttribute('rows') || '24', 10);
    const minRows = Number.isFinite(rows) ? Math.max(rows, 8) : 24;
    const minHeight = Math.max(240, Math.round(minRows * 20));

    const shell = document.createElement('div');
    shell.className = `module-code-editor-shell mt-1${fillMode ? ' module-code-editor-shell--fill' : ''}`;
    shell.style.setProperty('--module-editor-min-height', `${minHeight}px`);

    const shouldSyncNativeEvents = textarea.dataset.editorSyncNative !== '0';
    let pendingChangeOrigin = 'raw';

    textarea.insertAdjacentElement('beforebegin', shell);

    const editorTheme = EditorView.theme({
        '&': fillMode
            ? {
                height: '100%',
                minHeight: '0',
                fontSize: '0.75rem',
                lineHeight: '1.5',
            }
            : {
                minHeight: 'var(--module-editor-min-height)',
                fontSize: '0.75rem',
                lineHeight: '1.5',
            },
        '.cm-scroller': fillMode
            ? {
                height: '100%',
                overflow: 'auto',
                fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
            }
            : {
                minHeight: 'var(--module-editor-min-height)',
                fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
            },
        '.cm-content': fillMode
            ? {
                minHeight: '100%',
                tabSize: '4',
            }
            : {
                minHeight: 'var(--module-editor-min-height)',
                tabSize: '4',
            },
        '.cm-gutters': {
            borderRight: '1px solid rgb(226 232 240)',
            backgroundColor: 'rgb(248 250 252)',
        },
    });

    const extensions = [
        basicSetup,
        languageExtension(language),
        editorTheme,
        EditorView.updateListener.of((update) => {
            if (!update.docChanged) {
                return;
            }

            textarea.value = update.state.doc.toString();

            textarea.dispatchEvent(new CustomEvent('module-code-editor:change', {
                bubbles: true,
                detail: {
                    value: textarea.value,
                    origin: pendingChangeOrigin,
                },
            }));

            pendingChangeOrigin = 'raw';

            if (shouldSyncNativeEvents) {
                syncTextareaEvents(textarea);
            }
        }),
    ];

    if (isReadOnly) {
        extensions.push(EditorState.readOnly.of(true));
        extensions.push(EditorView.editable.of(false));
    } else {
        extensions.push(keymap.of([indentWithTab]));
    }

    const view = new EditorView({
        state: EditorState.create({
            doc: textarea.value ?? '',
            extensions,
        }),
        parent: shell,
    });

    // Expose a tiny imperative bridge so Alpine state can update the live
    // CodeMirror document directly instead of only mutating the hidden textarea.
    textarea.__moduleCodeEditor = {
        setValue(nextValue, origin = 'guided') {
            const normalizedValue = typeof nextValue === 'string' ? nextValue : '';

            if (view.state.doc.toString() === normalizedValue) {
                textarea.value = normalizedValue;
                return false;
            }

            pendingChangeOrigin = origin === 'raw' ? 'raw' : 'guided';
            view.dispatch({
                changes: {
                    from: 0,
                    to: view.state.doc.length,
                    insert: normalizedValue,
                },
            });

            return true;
        },
        getValue() {
            return view.state.doc.toString();
        },
        undo() {
            pendingChangeOrigin = 'raw';

            return undo(view);
        },
        redo() {
            pendingChangeOrigin = 'raw';

            return redo(view);
        },
        focus() {
            view.focus();
        },
    };

    textarea.style.display = 'none';
    textarea.dataset.editorReady = '1';

    const form = textarea.closest('form');
    if (form !== null) {
        form.addEventListener('reset', () => {
            window.requestAnimationFrame(() => {
                view.dispatch({
                    changes: {
                        from: 0,
                        to: view.state.doc.length,
                        insert: textarea.value ?? '',
                    },
                });
            });
        });
    }

    textarea.addEventListener('module-code-editor:set-value', (event) => {
        const detail = event instanceof CustomEvent ? event.detail : null;
        const nextValue = typeof detail?.value === 'string'
            ? detail.value
            : '';

        textarea.__moduleCodeEditor.setValue(nextValue);
    });
}

/**
 * Scan the page for module editor textareas and upgrade each one once.
 */
export function initModuleCodeEditors() {
    document
        .querySelectorAll('textarea[data-module-code-editor]')
        .forEach((node) => setupModuleCodeEditor(node));
}
