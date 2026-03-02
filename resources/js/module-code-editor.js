import { EditorState } from '@codemirror/state';
import { EditorView, keymap } from '@codemirror/view';
import { indentWithTab } from '@codemirror/commands';
import { basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { javascript } from '@codemirror/lang-javascript';
import { json } from '@codemirror/lang-json';

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

function syncTextareaEvents(textarea) {
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    textarea.dispatchEvent(new Event('change', { bubbles: true }));
}

function setupModuleCodeEditor(textarea) {
    if (!(textarea instanceof HTMLTextAreaElement) || textarea.dataset.editorReady === '1') {
        return;
    }

    const language = (textarea.dataset.editorLanguage || 'html').toLowerCase();
    const isReadOnly = textarea.dataset.editorReadonly === '1'
        || textarea.hasAttribute('readonly')
        || textarea.hasAttribute('disabled');
    const rows = Number.parseInt(textarea.getAttribute('rows') || '24', 10);
    const minRows = Number.isFinite(rows) ? Math.max(rows, 8) : 24;
    const minHeight = Math.max(240, Math.round(minRows * 20));

    const shell = document.createElement('div');
    shell.className = 'module-code-editor-shell mt-1';
    shell.style.setProperty('--module-editor-min-height', `${minHeight}px`);

    textarea.insertAdjacentElement('beforebegin', shell);

    const editorTheme = EditorView.theme({
        '&': {
            minHeight: 'var(--module-editor-min-height)',
            fontSize: '0.75rem',
            lineHeight: '1.5',
        },
        '.cm-scroller': {
            minHeight: 'var(--module-editor-min-height)',
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
        },
        '.cm-content': {
            minHeight: 'var(--module-editor-min-height)',
            tabSize: '4',
        },
        '.cm-gutters': {
            borderRight: '1px solid rgb(229 231 235)',
            backgroundColor: 'rgb(249 250 251)',
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
            syncTextareaEvents(textarea);
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

        if (view.state.doc.toString() === nextValue) {
            return;
        }

        view.dispatch({
            changes: {
                from: 0,
                to: view.state.doc.length,
                insert: nextValue,
            },
        });
    });
}

export function initModuleCodeEditors() {
    document
        .querySelectorAll('textarea[data-module-code-editor]')
        .forEach((node) => setupModuleCodeEditor(node));
}
