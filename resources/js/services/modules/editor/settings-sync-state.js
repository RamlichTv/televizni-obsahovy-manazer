/**
 * Reactive Guided/Raw settings synchronization.
 *
 * Guided edits continuously mirror into the Raw payload store, and valid Raw
 * edits continuously hydrate the Guided controls. The small sync guard keeps
 * programmatic updates from bouncing back recursively.
 */
export const createSettingsSyncState = () => ({
    settingsSyncReady: false,
    isSyncingSettings: false,
    settingsSyncOrigin: null,

    runSettingsSync(origin, callback) {
        if (this.isSyncingSettings) {
            return;
        }

        this.isSyncingSettings = true;
        this.settingsSyncOrigin = origin;

        try {
            return callback();
        } finally {
            this.settingsSyncOrigin = null;
            this.isSyncingSettings = false;
        }
    },

    mirrorBuilderStateIntoRawJson() {
        this.runSettingsSync('guided', () => {
            this.syncRawJsonFromBuilder();
        });
    },

    hydrateBuilderStateFromRawJson(key) {
        this.runSettingsSync('raw', () => {
            this.syncBuilderFromRawJson(key);
        });
    },

    registerSettingsSyncWatchers() {
        if (this.settingsSyncReady || typeof this.$watch !== 'function') {
            return;
        }

        this.settingsSyncReady = true;
        // Sync is driven by explicit editor/input events instead of deep Alpine
        // watchers because nested mutation was too brittle for CodeMirror-backed
        // editors and dynamic schema rows.
    },
});
