/**
 * Primary application entrypoint.
 * It bootstraps Alpine, shared editor modules, and the global feedback system used across layouts.
 */

import './bootstrap';
import './services/dashboard/layout-editor';
import './services/profiles/layout-editor';
import './services/profiles/preview';
import { initializeFeedbackSystem } from './feedback';
import { moduleEditor, moduleFormEditor } from './services/modules/form-editor';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.moduleEditor = moduleEditor;
window.moduleFormEditor = moduleFormEditor;

/**
 * Register global confirm interception before Alpine mounts page components.
 */
initializeFeedbackSystem();

Alpine.start();

let moduleCodeEditorLoader = null;

/**
 * Load the CodeMirror chunk once and upgrade any newly inserted module editors.
 */
const loadModuleCodeEditor = () => {
    if (document.querySelector('textarea[data-module-code-editor]') === null) {
        return;
    }

    moduleCodeEditorLoader ??= import('./module-code-editor');

    moduleCodeEditorLoader
        .then(({ initModuleCodeEditors }) => {
            initModuleCodeEditors();
        })
        .catch(() => {
            // Keep plain textareas usable if editor chunk fails to load.
        });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadModuleCodeEditor, { once: true });
} else {
    loadModuleCodeEditor();
}

window.addEventListener('module-code-editor:refresh', loadModuleCodeEditor);
