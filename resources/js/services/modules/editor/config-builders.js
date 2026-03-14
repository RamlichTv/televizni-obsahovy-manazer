import {
    clampLimit,
    normalizeIntegerArray,
    normalizeStringArray,
    stringifyJson,
    toObject,
} from './helpers';
import { buildDataSchemaObject, buildModuleDataObject } from './schema-fields';

/**
 * Pure payload builders for the guided module settings UI.
 */

/**
 * Build the guided `data_schema` payload.
 *
 * Strict mode is used for preview/save validation, while permissive mode is
 * used for live mirroring so incomplete draft rows can be skipped quietly.
 *
 * @param {Array<Object<string, unknown>>} schemaFields
 * @param {{mode?: 'strict'|'permissive'}} [options={}]
 * @returns {{ json: string, errors: Array<string> }}
 */
export const buildDataSchemaJson = (schemaFields, options = {}) => {
    const { value, errors } = buildDataSchemaObject(
        Array.isArray(schemaFields) ? schemaFields : [],
        options,
    );

    return {
        json: stringifyJson(value),
        errors,
    };
};

/**
 * Build the guided `module_data` payload.
 *
 * @param {Array<Object<string, unknown>>} schemaFields
 * @param {{mode?: 'strict'|'permissive'}} [options={}]
 * @returns {{ json: string, errors: Array<string> }}
 */
export const buildModuleDataJson = (schemaFields, options = {}) => {
    const { value, errors } = buildModuleDataObject(
        Array.isArray(schemaFields) ? schemaFields : [],
        options,
    );

    return {
        json: stringifyJson(value),
        errors,
    };
};

/**
 * @param {Object<string, unknown>} baseDataSource
 * @param {Object<string, unknown>} providerSettings
 * @returns {string}
 */
export const buildDataSourceJson = (baseDataSource, providerSettings) => {
    const base = toObject(baseDataSource);
    const filters = toObject(base.filters);
    const authoredBy = typeof providerSettings.authoredBy === 'string'
        ? providerSettings.authoredBy.trim().toLowerCase()
        : '';

    if (authoredBy === 'viewer') {
        filters.authored_by = 'viewer';
        delete filters.author_id;
        delete filters.author_ids;
    } else {
        delete filters.authored_by;
        delete filters.author_id;
        delete filters.author_ids;
    }

    const typeNames = normalizeStringArray(providerSettings.typeNames);
    if (typeNames.length > 0) {
        filters.type_names = typeNames;
        if (typeNames.length === 1) {
            filters.type_name = typeNames[0];
        } else {
            delete filters.type_name;
        }

        // Guided mode only exposes type-name filters, so raw-only ID filters
        // must not linger in the generated payload.
        delete filters.type_ids;
    } else {
        delete filters.type_names;
        delete filters.type_name;
        delete filters.type_ids;
    }

    const statusNames = normalizeStringArray(providerSettings.statusNames);
    if (statusNames.length > 0) {
        filters.status_names = statusNames;
        if (statusNames.length === 1) {
            filters.status_name = statusNames[0];
        } else {
            delete filters.status_name;
        }

        // Guided mode intentionally normalizes status filters to names only.
        delete filters.status_ids;
    } else {
        delete filters.status_names;
        delete filters.status_name;
        delete filters.status_ids;
    }

    const tagIds = normalizeIntegerArray(providerSettings.tagIds);
    if (tagIds.length > 0) {
        filters.tag_ids = tagIds;

        // Guided mode uses tag IDs, so legacy name-based tag filters must be removed.
        delete filters.tag_name;
        delete filters.tag_names;
    } else {
        delete filters.tag_ids;
        delete filters.tag_name;
        delete filters.tag_names;
    }

    base.provider = providerSettings.provider || base.provider || 'content_objects';
    base.filters = filters;
    base.order_by = providerSettings.orderBy || base.order_by || 'published_at';
    base.order_direction = providerSettings.orderDirection || base.order_direction || 'desc';
    base.limit = clampLimit(providerSettings.limit);

    return stringifyJson(base);
};
