{{--
    Settings pane for the module editor.
    Guided mode handles common authoring, while raw mode exposes the exact JSON contracts.
--}}

<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                @click="setSettingsMode('builder')"
                :class="settingsModeButtonClass('builder')"
                class="rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest transition"
            >
                {{ __('Guided UI') }}
            </button>
            <button
                type="button"
                @click="setSettingsMode('raw')"
                :class="settingsModeButtonClass('raw')"
                class="rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-widest transition"
            >
                {{ __('Raw JSON') }}
            </button>
        </div>

        <p class="mt-3 text-sm text-gray-500">
            {{ __('Guided mode helps you build the provider, data schema, and module data step by step. Raw JSON remains available when you need exact control.') }}
        </p>
    </div>

    <div
        x-show="settingsError.trim() !== ''"
        x-cloak
        class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm"
        x-text="settingsError"
    ></div>

    <div x-show="settingsMode === 'builder'" x-cloak>
        @include('services.modules.partials.form.settings-builder', [
            'providers' => $providers,
            'pickers' => $pickers,
            'settingsUi' => $settingsUi,
        ])
    </div>

    <div x-show="settingsMode === 'raw'" x-cloak>
        @include('services.modules.partials.form.settings-raw', [
            'rawJson' => $rawJson,
        ])
    </div>
</div>
