import './bootstrap';
import './services/profiles/layout-editor';
import './services/profiles/preview';
import { moduleFormEditor } from './services/modules/form-editor';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.moduleFormEditor = moduleFormEditor;

Alpine.start();

const loadModuleCodeEditor = () => {
    if (document.querySelector('textarea[data-module-code-editor]') === null) {
        return;
    }

    import('./module-code-editor')
        .then(({ initModuleCodeEditors }) => {
            initModuleCodeEditors();
        })
        .catch(() => {
            // Keep plain textareas usable if editor chunk fails to load.
        });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadModuleCodeEditor);
} else {
    loadModuleCodeEditor();
}
