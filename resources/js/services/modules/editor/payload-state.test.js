import assert from 'node:assert/strict';
import test from 'node:test';

import {
    buildInitialPayloadState,
    createPayloadChannel,
    updatePayloadChannel,
} from './payload-state.js';

test('createPayloadChannel parses valid object JSON', () => {
    const channel = createPayloadChannel('{"title":"Example"}');

    assert.equal(channel.text, '{"title":"Example"}');
    assert.equal(channel.isValid, true);
    assert.deepEqual(channel.parsed, { title: 'Example' });
    assert.equal(channel.lastOrigin, 'init');
});

test('updatePayloadChannel keeps the last valid parsed payload when JSON becomes invalid', () => {
    const previous = createPayloadChannel('{"title":"Before"}', 'raw');
    const next = updatePayloadChannel(previous, '{"title":', 'raw');

    assert.equal(next.text, '{"title":');
    assert.equal(next.isValid, false);
    assert.deepEqual(next.parsed, { title: 'Before' });
    assert.equal(next.lastOrigin, 'raw');
});

test('buildInitialPayloadState creates independent channels for each payload', () => {
    const state = buildInitialPayloadState({
        dataSchema: '{"title":{"type":"string","default":"Hello"}}',
        moduleData: '{"title":"Hello"}',
        dataSource: '{"provider":"content_objects"}',
    });

    assert.equal(state.dataSchema.isValid, true);
    assert.equal(state.moduleData.isValid, true);
    assert.equal(state.dataSource.isValid, true);
    assert.deepEqual(state.moduleData.parsed, { title: 'Hello' });
});
