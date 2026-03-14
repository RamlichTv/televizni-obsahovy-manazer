import { parseJsonObject, toString } from './helpers.js';

/**
 * Build and update the per-payload state used by the module settings editor.
 *
 * Each payload keeps its exact text plus the last valid parsed object so Raw
 * JSON can stay editable while Guided controls continue to reflect only valid
 * payloads.
 */

/**
 * Create one payload channel.
 *
 * @param {unknown} text
 * @param {'init'|'guided'|'raw'} [origin='init']
 * @returns {{ text: string, parsed: Object<string, unknown>|null, isValid: boolean, lastOrigin: 'init'|'guided'|'raw' }}
 */
export const createPayloadChannel = (text, origin = 'init') => {
    const normalizedText = toString(text, '{}');
    const parsed = parseJsonObject(normalizedText);

    return {
        text: normalizedText,
        parsed,
        isValid: parsed !== null,
        lastOrigin: origin,
    };
};

/**
 * Create the initial payload-state object for all three module settings JSON contracts.
 *
 * @param {Object<string, unknown>} rawJson
 * @returns {{ dataSchema: ReturnType<typeof createPayloadChannel>, moduleData: ReturnType<typeof createPayloadChannel>, dataSource: ReturnType<typeof createPayloadChannel> }}
 */
export const buildInitialPayloadState = (rawJson = {}) => ({
    dataSchema: createPayloadChannel(rawJson.dataSchema),
    moduleData: createPayloadChannel(rawJson.moduleData),
    dataSource: createPayloadChannel(rawJson.dataSource),
});

/**
 * Update one payload channel while preserving the last valid parsed object.
 *
 * @param {{ parsed: Object<string, unknown>|null }|null|undefined} previous
 * @param {unknown} text
 * @param {'init'|'guided'|'raw'} [origin='raw']
 * @returns {{ text: string, parsed: Object<string, unknown>|null, isValid: boolean, lastOrigin: 'init'|'guided'|'raw' }}
 */
export const updatePayloadChannel = (previous, text, origin = 'raw') => {
    const normalizedText = toString(text, '{}');
    const parsed = parseJsonObject(normalizedText);

    return {
        text: normalizedText,
        parsed: parsed ?? previous?.parsed ?? null,
        isValid: parsed !== null,
        lastOrigin: origin,
    };
};
