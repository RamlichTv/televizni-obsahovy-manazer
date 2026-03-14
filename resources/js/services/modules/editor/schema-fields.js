import { stringifyJson, toObject, toString } from './helpers';

/**
 * Supported field types for guided module settings.
 */
export const schemaFieldTypes = [
    { value: 'string', label: 'String' },
    { value: 'integer', label: 'Integer' },
    { value: 'boolean', label: 'Boolean' },
    { value: 'object', label: 'Object' },
    { value: 'array', label: 'Array' },
];

const supportedTypes = new Set(schemaFieldTypes.map((option) => option.value));

/**
 * @param {unknown} mode
 * @returns {'strict'|'permissive'}
 */
const normalizeBuildMode = (mode) => mode === 'permissive' ? 'permissive' : 'strict';

/**
 * @param {unknown} value
 * @returns {boolean}
 */
const isPlainObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);

/**
 * @returns {string}
 */
const nextFieldId = () => `schema-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;

/**
 * @param {unknown} value
 * @param {string} [fallback='string']
 * @returns {'string'|'integer'|'boolean'|'object'|'array'}
 */
const normalizeType = (value, fallback = 'string') => {
    const normalized = toString(value, fallback).toLowerCase();

    if (supportedTypes.has(normalized)) {
        return /** @type {'string'|'integer'|'boolean'|'object'|'array'} */ (normalized);
    }

    return /** @type {'string'|'integer'|'boolean'|'object'|'array'} */ (fallback);
};

/**
 * @param {unknown} value
 * @param {string} [fallback='string']
 * @returns {'string'|'integer'|'boolean'|'object'|'array'}
 */
const inferType = (value, fallback = 'string') => {
    if (Array.isArray(value)) {
        return 'array';
    }

    if (isPlainObject(value)) {
        return 'object';
    }

    if (typeof value === 'boolean') {
        return 'boolean';
    }

    if (typeof value === 'number' && Number.isInteger(value)) {
        return 'integer';
    }

    return normalizeType(fallback);
};

/**
 * @param {unknown} value
 * @returns {string}
 */
const normalizeNumberMeta = (value) => {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const numeric = Number(value);

    return Number.isFinite(numeric) ? String(numeric) : '';
};

/**
 * @param {'string'|'integer'|'boolean'|'object'|'array'} type
 * @param {unknown} value
 * @returns {string|boolean}
 */
const serializeValueForUi = (type, value) => {
    switch (type) {
        case 'integer': {
            if (value === null || value === undefined || value === '') {
                return '';
            }

            const numeric = Number(value);

            return Number.isFinite(numeric) ? String(Math.trunc(numeric)) : '';
        }

        case 'boolean':
            return Boolean(value);

        case 'object':
            return stringifyJson(isPlainObject(value) ? value : {});

        case 'array':
            return stringifyJson(Array.isArray(value) ? value : []);

        default:
            return value === null || value === undefined ? '' : String(value);
    }
};

/**
 * @param {'string'|'integer'|'boolean'|'object'|'array'} type
 * @param {unknown} rawValue
 * @param {string} key
 * @param {string} label
 * @returns {{ valid: boolean, value: unknown, error: string }}
 */
const parseFieldValue = (type, rawValue, key, label) => {
    switch (type) {
        case 'integer': {
            if (rawValue === '' || rawValue === null || rawValue === undefined) {
                return { valid: true, value: 0, error: '' };
            }

            const numeric = Number(rawValue);
            if (!Number.isFinite(numeric)) {
                return { valid: false, value: 0, error: `Field "${key}" has an invalid ${label} integer.` };
            }

            return { valid: true, value: Math.trunc(numeric), error: '' };
        }

        case 'boolean':
            return { valid: true, value: Boolean(rawValue), error: '' };

        case 'object':
        case 'array': {
            const fallback = type === 'object' ? {} : [];
            const source = typeof rawValue === 'string' ? rawValue.trim() : stringifyJson(rawValue ?? fallback);

            if (source === '') {
                return { valid: true, value: fallback, error: '' };
            }

            try {
                const parsed = JSON.parse(source);
                const valid = type === 'object' ? isPlainObject(parsed) : Array.isArray(parsed);

                if (!valid) {
                    return {
                        valid: false,
                        value: fallback,
                        error: `Field "${key}" must use valid ${type} JSON for ${label}.`,
                    };
                }

                return { valid: true, value: parsed, error: '' };
            } catch {
                return {
                    valid: false,
                    value: fallback,
                    error: `Field "${key}" must use valid ${type} JSON for ${label}.`,
                };
            }
        }

        default:
            return {
                valid: true,
                value: rawValue === null || rawValue === undefined ? '' : String(rawValue),
                error: '',
            };
    }
};

/**
 * Create an empty guided schema row.
 *
 * New rows start expanded so authors land directly in the field they just added.
 *
 * @returns {{id: string, key: string, type: string, defaultValue: string|boolean, value: string|boolean, min: string, max: string, expanded: boolean, extras: Object<string, unknown>}}
 */
export const createEmptySchemaField = () => ({
    id: nextFieldId(),
    key: '',
    type: 'string',
    defaultValue: '',
    value: '',
    min: '',
    max: '',
    expanded: true,
    extras: {},
});

/**
 * @param {Record<string, unknown>} dataSchema
 * @param {Record<string, unknown>} moduleData
 * @returns {Array<{id: string, key: string, type: string, defaultValue: string|boolean, value: string|boolean, min: string, max: string, expanded: boolean, extras: Object<string, unknown>}>}
 */
export const schemaFieldsFromData = (dataSchema, moduleData) => {
    const schema = toObject(dataSchema);
    const data = toObject(moduleData);
    const keys = [...new Set([...Object.keys(schema), ...Object.keys(data)])];

    return keys.map((key) => {
        const rawDefinition = isPlainObject(schema[key]) ? { ...toObject(schema[key]) } : {};
        const defaultValue = Object.prototype.hasOwnProperty.call(rawDefinition, 'default')
            ? rawDefinition.default
            : data[key];
        const inferredType = inferType(
            data[key],
            inferType(defaultValue, normalizeType(rawDefinition.type, 'string')),
        );

        const { type, default: ignoredDefault, min: ignoredMin, max: ignoredMax, ...extras } = rawDefinition;

        return {
            id: nextFieldId(),
            key,
            type: inferredType,
            defaultValue: serializeValueForUi(inferredType, defaultValue),
            value: serializeValueForUi(inferredType, key in data ? data[key] : defaultValue),
            min: inferredType === 'integer' ? normalizeNumberMeta(rawDefinition.min) : '',
            max: inferredType === 'integer' ? normalizeNumberMeta(rawDefinition.max) : '',
            expanded: false,
            extras,
        };
    });
};

/**
 * Rebuild schema fields while preserving stable row identity and accordion state.
 *
 * Matching keys reuse their previous `id` and `expanded` values so Guided inputs
 * do not lose focus or collapse when Raw JSON updates the underlying schema.
 *
 * @param {Array<{id: string, key: string, expanded: boolean}>} existingFields
 * @param {Record<string, unknown>} dataSchema
 * @param {Record<string, unknown>} moduleData
 * @returns {Array<{id: string, key: string, type: string, defaultValue: string|boolean, value: string|boolean, min: string, max: string, expanded: boolean, extras: Object<string, unknown>}>}
 */
export const mergeSchemaFieldsFromData = (existingFields, dataSchema, moduleData) => {
    const previousByKey = new Map();

    (Array.isArray(existingFields) ? existingFields : []).forEach((field) => {
        const key = toString(field?.key).trim();
        if (key !== '' && !previousByKey.has(key)) {
            previousByKey.set(key, field);
        }
    });

    return schemaFieldsFromData(dataSchema, moduleData).map((field) => {
        const previous = previousByKey.get(field.key);

        if (!previous) {
            return field;
        }

        return {
            ...field,
            id: toString(previous.id, field.id),
            expanded: previous.expanded === true,
        };
    });
};

/**
 * @param {{type: string, defaultValue: string|boolean, value: string|boolean, min: string, max: string}} field
 * @param {unknown} nextType
 * @returns {{type: string, defaultValue: string|boolean, value: string|boolean, min: string, max: string}}
 */
export const coerceSchemaFieldType = (field, nextType) => {
    const type = normalizeType(nextType, inferType(field.defaultValue));

    switch (type) {
        case 'integer': {
            const numericDefault = Number(field.defaultValue);
            const numericValue = Number(field.value);

            return {
                ...field,
                type,
                defaultValue: Number.isFinite(numericDefault) ? String(Math.trunc(numericDefault)) : '0',
                value: Number.isFinite(numericValue) ? String(Math.trunc(numericValue)) : '0',
                min: field.min,
                max: field.max,
            };
        }

        case 'boolean':
            return {
                ...field,
                type,
                defaultValue: Boolean(field.defaultValue),
                value: Boolean(field.value),
                min: '',
                max: '',
            };

        case 'object':
            return {
                ...field,
                type,
                defaultValue: serializeValueForUi('object', field.defaultValue),
                value: serializeValueForUi('object', field.value),
                min: '',
                max: '',
            };

        case 'array':
            return {
                ...field,
                type,
                defaultValue: serializeValueForUi('array', field.defaultValue),
                value: serializeValueForUi('array', field.value),
                min: '',
                max: '',
            };

        default:
            return {
                ...field,
                type: 'string',
                defaultValue: field.defaultValue === null || field.defaultValue === undefined ? '' : String(field.defaultValue),
                value: field.value === null || field.value === undefined ? '' : String(field.value),
                min: '',
                max: '',
            };
    }
};

/**
 * Build a best-effort schema object for live mirroring.
 *
 * Incomplete draft rows should not poison the mirrored raw JSON, so this
 * builder skips invalid rows instead of surfacing blocking errors.
 *
 * @param {Array<{key: string, type: string, defaultValue: string|boolean, min: string, max: string, extras: Object<string, unknown>}>} fields
 * @param {{mode?: 'strict'|'permissive'}} [options={}]
 * @returns {{ value: Record<string, unknown>, errors: Array<string> }}
 */
export const buildDataSchemaObject = (fields, options = {}) => {
    const mode = normalizeBuildMode(options.mode);
    const result = {};
    const errors = [];
    const seenKeys = new Set();

    fields.forEach((field, index) => {
        const key = toString(field.key).trim();
        if (key === '') {
            if (mode === 'strict') {
                errors.push(`Schema field ${index + 1} needs a key name.`);
            }
            return;
        }

        if (seenKeys.has(key)) {
            if (mode === 'strict') {
                errors.push(`Schema field "${key}" is duplicated.`);
            }
            return;
        }

        seenKeys.add(key);

        const type = normalizeType(field.type, inferType(field.defaultValue));
        const parsedDefault = parseFieldValue(type, field.defaultValue, key, 'default value');
        if (!parsedDefault.valid) {
            if (mode === 'strict') {
                errors.push(parsedDefault.error);
            }
            return;
        }

        const definition = {
            ...toObject(field.extras),
            type,
            default: parsedDefault.value,
        };

        if (type === 'integer') {
            if (field.min !== '') {
                const numericMin = Number(field.min);
                if (!Number.isFinite(numericMin)) {
                    if (mode === 'strict') {
                        errors.push(`Field "${key}" has an invalid minimum.`);
                    }
                    return;
                }

                definition.min = Math.trunc(numericMin);
            }

            if (field.max !== '') {
                const numericMax = Number(field.max);
                if (!Number.isFinite(numericMax)) {
                    if (mode === 'strict') {
                        errors.push(`Field "${key}" has an invalid maximum.`);
                    }
                    return;
                }

                definition.max = Math.trunc(numericMax);
            }
        }

        result[key] = definition;
    });

    return { value: result, errors };
};

/**
 * @param {Array<{key: string, type: string, value: string|boolean}>} fields
 * @param {{mode?: 'strict'|'permissive'}} [options={}]
 * @returns {{ value: Record<string, unknown>, errors: Array<string> }}
 */
export const buildModuleDataObject = (fields, options = {}) => {
    const mode = normalizeBuildMode(options.mode);
    const result = {};
    const errors = [];

    fields.forEach((field, index) => {
        const key = toString(field.key).trim();
        if (key === '') {
            if (mode === 'strict') {
                errors.push(`Schema field ${index + 1} needs a key name before module data can be generated.`);
            }
            return;
        }

        const type = normalizeType(field.type, inferType(field.value));
        const parsedValue = parseFieldValue(type, field.value, key, 'module data');
        if (!parsedValue.valid) {
            if (mode === 'strict') {
                errors.push(parsedValue.error);
            }
            return;
        }

        result[key] = parsedValue.value;
    });

    return { value: result, errors };
};
