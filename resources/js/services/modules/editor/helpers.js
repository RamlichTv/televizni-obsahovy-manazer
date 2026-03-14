/**
 * Shared normalization and parsing helpers for the module editor.
 */

/**
 * @param {unknown} value
 * @returns {Object<string, unknown>}
 */
export const toObject = (value) => {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        return { ...value };
    }

    return {};
};

/**
 * @param {unknown} value
 * @param {string} [fallback='']
 * @returns {string}
 */
export const toString = (value, fallback = '') => {
    return typeof value === 'string' ? value : fallback;
};

/**
 * @param {unknown} value
 * @returns {Array<string>}
 */
export const toStringArray = (value) => {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .map((item) => toString(item).trim())
        .filter((item) => item !== '');
};

/**
 * @param {Array<unknown>} values
 * @returns {Array<string>}
 */
export const normalizeStringArray = (values) => {
    if (!Array.isArray(values)) {
        return [];
    }

    const unique = new Set();

    values.forEach((value) => {
        const normalized = toString(value).trim();
        if (normalized !== '') {
            unique.add(normalized);
        }
    });

    return Array.from(unique);
};

/**
 * @param {unknown} value
 * @returns {Array<number>}
 */
export const normalizeIntegerArray = (value) => {
    if (!Array.isArray(value)) {
        return [];
    }

    const unique = new Set();

    value.forEach((entry) => {
        const numeric = Number.parseInt(String(entry), 10);
        if (Number.isInteger(numeric) && numeric > 0) {
            unique.add(numeric);
        }
    });

    return Array.from(unique);
};

/**
 * @param {unknown} value
 * @returns {number}
 */
export const clampLimit = (value) => {
    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return 1;
    }

    return Math.max(1, Math.floor(numeric));
};

/**
 * @param {unknown} value
 * @returns {string}
 */
export const stringifyJson = (value) => {
    try {
        return JSON.stringify(value ?? {}, null, 2) || '{}';
    } catch {
        return '{}';
    }
};

/**
 * @param {unknown} value
 * @returns {Object<string, unknown>|null}
 */
export const parseJsonObject = (value) => {
    if (typeof value !== 'string') {
        return null;
    }

    try {
        const parsed = JSON.parse(value);

        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
            return parsed;
        }

        return null;
    } catch {
        return null;
    }
};

/**
 * @param {unknown} value
 * @returns {'vertical'|'horizontal'|'preview'}
 */
export const normalizeLayout = (value) => {
    return value === 'horizontal' || value === 'preview' ? value : 'vertical';
};

/**
 * @param {unknown} value
 * @returns {'code'|'settings'|'meta'}
 */
export const normalizeSidebarTab = (value) => {
    return value === 'settings' || value === 'meta' ? value : 'code';
};

/**
 * @param {unknown} value
 * @returns {'html'|'css'|'js'}
 */
export const normalizeActiveTab = (value) => {
    return value === 'css' || value === 'js' ? value : 'html';
};

/**
 * @param {unknown} value
 * @param {'vertical'|'horizontal'|'preview'} layout
 * @returns {number}
 */
export const clampSplitPosition = (value, layout) => {
    if (layout === 'preview') {
        return 100;
    }

    const numeric = Number(value);
    if (!Number.isFinite(numeric)) {
        return 47;
    }

    return Math.max(22, Math.min(78, numeric));
};

/**
 * @param {Response} response
 * @returns {Promise<Object<string, unknown>>}
 */
export const parseJsonResponse = async (response) => {
    try {
        const payload = await response.json();

        return payload && typeof payload === 'object' && !Array.isArray(payload)
            ? payload
            : {};
    } catch {
        return {};
    }
};

/**
 * @param {Object<string, unknown>} payload
 * @param {number} status
 * @returns {string}
 */
export const formatPreviewError = (payload, status) => {
    const errors = payload.errors;
    if (errors && typeof errors === 'object' && !Array.isArray(errors)) {
        const messages = Object.values(errors)
            .flatMap((value) => Array.isArray(value) ? value : [value])
            .map((value) => toString(value).trim())
            .filter((value) => value !== '');

        if (messages.length > 0) {
            return messages.join(' ');
        }
    }

    const message = toString(payload.message).trim();
    if (message !== '') {
        return message;
    }

    if (status >= 500) {
        return 'Preview failed because the server returned an unexpected error.';
    }

    return 'Preview could not be rendered.';
};

/**
 * Read a textarea value directly from the DOM when CodeMirror is the authoritative source.
 *
 * @param {unknown} ref
 * @param {string} fallback
 * @returns {string}
 */
export const readEditorValue = (ref, fallback = '{}') => {
    return ref instanceof HTMLTextAreaElement ? ref.value : fallback;
};
