{{--
    Component: Profile Settings Form
    Purpose: Provides editable profile metadata and advanced settings in a standalone, reusable panel.
    Data Contract: Requires a `$profile` model and relies on Laravel's `old()` + validation error bag.
--}}

@props(['profile'])

@php
    /**
     * Normalize profile settings so the UI can always render predictable structures.
     * This avoids repetitive null checks in the template and preserves old input.
     */
    $settings = old('settings', $profile->settings ?? []);
    if (!is_array($settings)) {
        $settings = [];
    }

    // Group settings by feature area to simplify form field defaults.
    $playback = is_array($settings['playback'] ?? null) ? $settings['playback'] : [];
    $display = is_array($settings['display'] ?? null) ? $settings['display'] : [];
    $animation = is_array($settings['animation'] ?? null) ? $settings['animation'] : [];

    // Support both legacy persisted `custom` object and form-driven `custom_pairs` rows.
    $customSettings = [];
    if (is_array($settings['custom_pairs'] ?? null)) {
        foreach ($settings['custom_pairs'] as $pair) {
            if (!is_array($pair)) {
                continue;
            }

            $customSettings[] = [
                'key' => (string) ($pair['key'] ?? ''),
                'value' => (string) ($pair['value'] ?? ''),
            ];
        }
    } elseif (is_array($settings['custom'] ?? null)) {
        foreach ($settings['custom'] as $key => $value) {
            $customSettings[] = [
                'key' => (string) $key,
                'value' => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES),
            ];
        }
    }

    // Auto-expand advanced section when user is returning from validation errors.
    $settingsOpen = old('settings') !== null || $errors->has('settings') || $errors->has('settings.playback.auto_rotate') || $errors->has('settings.playback.loop_pages') || $errors->has('settings.display.show_page_name') || $errors->has('settings.display.show_page_indicators') || $errors->has('settings.animation.transition') || $errors->has('settings.animation.transition_duration_ms') || $errors->has('settings.custom_pairs') || $errors->has('settings.custom_pairs.*.key') || $errors->has('settings.custom_pairs.*.value');
    $transition = (string) old('settings.animation.transition', $animation['transition'] ?? 'slide');
@endphp

{{-- Main metadata form: name/slug/description are saved through profile.update route. --}}
<form
    method="POST"
    action="{{ route('profile.update', ['profile' => $profile]) }}"
    class="space-y-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
    x-data="{ customSettings: @js($customSettings) }"
>
    @csrf
    @method('PATCH')

    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">{{ __('Profile Settings') }}</h2>
            <p class="text-xs text-gray-500">{{ __('Edit profile metadata and behavior settings any time.') }}</p>
        </div>

        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-md border border-transparent bg-[#077CB6] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#056796]"
        >
            {{ __('Save Settings') }}
        </button>
    </div>

    {{-- Core profile identity fields shown in list/detail views. --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <x-input-label for="profile_name" :value="__('Name')" />
            <x-text-input
                id="profile_name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', $profile->name)"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="profile_slug" :value="__('Slug (optional)')" />
            <x-text-input
                id="profile_slug"
                name="slug"
                type="text"
                class="mt-1 block w-full"
                :value="old('slug', $profile->slug)"
            />
            <p class="mt-1 text-xs text-gray-500">
                {{ __('Used in the URL. Leave empty to regenerate from profile name.') }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('slug')" />
        </div>

        <div class="md:col-span-2">
            <x-input-label for="profile_description" :value="__('Description')" />
            <x-textarea
                id="profile_description"
                name="description"
                rows="3"
                class="mt-1 block w-full"
            >{{ old('description', $profile->description) }}</x-textarea>
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>
    </div>

    {{-- Optional behavior tuning grouped in a foldable section to reduce visual noise. --}}
    <details class="rounded-lg border border-gray-200 p-4" @if($settingsOpen) open @endif>
        <summary class="cursor-pointer list-none">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('Advanced Settings (optional)') }}</h3>
                <span class="text-xs text-gray-500">{{ __('Expand / Collapse') }}</span>
            </div>
        </summary>

        <p class="mt-2 text-xs text-gray-500">
            {{ __('Optional behavior settings for screens that use this profile.') }}
        </p>

        {{-- Toggle switches controlling playback and display defaults. --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
            <label for="settings_playback_auto_rotate" class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 cursor-pointer">
                <div class="pr-3">
                    <p class="text-sm font-semibold text-gray-900">{{ __('Auto-rotate pages') }}</p>
                    <p class="text-xs text-gray-500">{{ __('Automatically move to the next page in the carousel.') }}</p>
                </div>
                <div class="relative inline-flex h-6 w-11 shrink-0 items-center">
                    <input type="hidden" name="settings[playback][auto_rotate]" value="0">
                    <input
                        id="settings_playback_auto_rotate"
                        name="settings[playback][auto_rotate]"
                        type="checkbox"
                        value="1"
                        class="peer sr-only"
                        @checked((bool) old('settings.playback.auto_rotate', $playback['auto_rotate'] ?? false))
                    >
                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#077CB6] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                </div>
            </label>

            <label for="settings_playback_loop_pages" class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 cursor-pointer">
                <div class="pr-3">
                    <p class="text-sm font-semibold text-gray-900">{{ __('Loop pages') }}</p>
                    <p class="text-xs text-gray-500">{{ __('After the last page, continue again from page 1.') }}</p>
                </div>
                <div class="relative inline-flex h-6 w-11 shrink-0 items-center">
                    <input type="hidden" name="settings[playback][loop_pages]" value="0">
                    <input
                        id="settings_playback_loop_pages"
                        name="settings[playback][loop_pages]"
                        type="checkbox"
                        value="1"
                        class="peer sr-only"
                        @checked((bool) old('settings.playback.loop_pages', $playback['loop_pages'] ?? true))
                    >
                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#077CB6] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                </div>
            </label>

            <label for="settings_display_show_page_name" class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 cursor-pointer">
                <div class="pr-3">
                    <p class="text-sm font-semibold text-gray-900">{{ __('Show page names') }}</p>
                    <p class="text-xs text-gray-500">{{ __('Display the current page name on the viewer.') }}</p>
                </div>
                <div class="relative inline-flex h-6 w-11 shrink-0 items-center">
                    <input type="hidden" name="settings[display][show_page_name]" value="0">
                    <input
                        id="settings_display_show_page_name"
                        name="settings[display][show_page_name]"
                        type="checkbox"
                        value="1"
                        class="peer sr-only"
                        @checked((bool) old('settings.display.show_page_name', $display['show_page_name'] ?? true))
                    >
                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#077CB6] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                </div>
            </label>

            <label for="settings_display_show_page_indicators" class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 cursor-pointer">
                <div class="pr-3">
                    <p class="text-sm font-semibold text-gray-900">{{ __('Show page indicators') }}</p>
                    <p class="text-xs text-gray-500">{{ __('Display page position indicators (for example 2 / 5).') }}</p>
                </div>
                <div class="relative inline-flex h-6 w-11 shrink-0 items-center">
                    <input type="hidden" name="settings[display][show_page_indicators]" value="0">
                    <input
                        id="settings_display_show_page_indicators"
                        name="settings[display][show_page_indicators]"
                        type="checkbox"
                        value="1"
                        class="peer sr-only"
                        @checked((bool) old('settings.display.show_page_indicators', $display['show_page_indicators'] ?? true))
                    >
                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#077CB6] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                </div>
            </label>
        </div>

        {{-- Transition mode + duration are rendered as explicit animation controls. --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-input-label for="settings_animation_transition" :value="__('Page Transition')" />
                <select
                    id="settings_animation_transition"
                    name="settings[animation][transition]"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                >
                    <option value="slide" @selected($transition === 'slide')>{{ __('Slide') }}</option>
                    <option value="fade" @selected($transition === 'fade')>{{ __('Fade') }}</option>
                    <option value="none" @selected($transition === 'none')>{{ __('None') }}</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('settings.animation.transition')" />
            </div>

            <div>
                <x-input-label for="settings_animation_transition_duration_ms" :value="__('Transition Duration (ms)')" />
                <x-text-input
                    id="settings_animation_transition_duration_ms"
                    name="settings[animation][transition_duration_ms]"
                    type="number"
                    min="100"
                    max="10000"
                    class="mt-1 block w-full"
                    :value="old('settings.animation.transition_duration_ms', $animation['transition_duration_ms'] ?? 300)"
                />
                <x-input-error class="mt-2" :messages="$errors->get('settings.animation.transition_duration_ms')" />
            </div>
        </div>

        {{-- Dynamic key/value editor for integration-specific metadata. --}}
        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">{{ __('Custom Settings') }}</h4>
                    <p class="text-xs text-gray-500">{{ __('Optional key/value pairs for integrations or special behavior.') }}</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                    @click.prevent="customSettings.push({ key: '', value: '' })"
                >
                    <x-icon name="plus-circle" class="h-4 w-4" />
                    {{ __('Add') }}
                </button>
            </div>

            <template x-for="(setting, index) in customSettings" :key="'profile-custom-setting-' + index">
                <div class="mt-3 grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2 items-end">
                    <div>
                        <x-input-label :value="__('Key')" />
                        <input
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                            :name="`settings[custom_pairs][${index}][key]`"
                            x-model="setting.key"
                            placeholder="{{ __('e.g. timezone') }}"
                        >
                    </div>
                    <div>
                        <x-input-label :value="__('Value')" />
                        <input
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                            :name="`settings[custom_pairs][${index}][value]`"
                            x-model="setting.value"
                            placeholder="{{ __('e.g. Europe/Prague') }}"
                        >
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-2 py-2 text-xs font-semibold text-red-600 hover:bg-red-50"
                        @click.prevent="customSettings.splice(index, 1)"
                        title="{{ __('Remove setting') }}"
                    >
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
            </template>

            <p x-show="customSettings.length === 0" x-cloak class="mt-3 text-xs text-gray-500">
                {{ __('No custom settings added.') }}
            </p>
        </div>

        <x-input-error class="mt-2" :messages="$errors->get('settings')" />
        <x-input-error class="mt-2" :messages="$errors->get('settings.playback.auto_rotate')" />
        <x-input-error class="mt-2" :messages="$errors->get('settings.playback.loop_pages')" />
        <x-input-error class="mt-2" :messages="$errors->get('settings.display.show_page_name')" />
        <x-input-error class="mt-2" :messages="$errors->get('settings.display.show_page_indicators')" />
        <x-input-error class="mt-2" :messages="$errors->get('settings.custom_pairs.*.key')" />
        <x-input-error class="mt-2" :messages="$errors->get('settings.custom_pairs.*.value')" />
    </details>
</form>
