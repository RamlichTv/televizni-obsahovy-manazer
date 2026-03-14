{{--
    Guided data-provider controls.
    This section drives the compiled data_source payload used by preview and save.
--}}
<x-services.modules.settings-accordion
    :title="__('Data Provider')"
    :description="__('Define how this module queries dynamic data from the database.')"
    :expanded="false"
>
    <div
        class="space-y-4"
        @input.debounce.50ms="notifyGuidedSettingsChanged()"
        @change="notifyGuidedSettingsChanged()"
    >
        {{-- Provider selector chooses which backend payload contract powers the module. --}}
        <div>
            <x-input-label :value="__('Provider')" />
            <select
                name="settings_provider"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                x-model="providerSettings.provider"
            >
                @foreach ($providers as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-sm text-gray-500">{{ __('Choose which backend provider supplies the payload used by the template.') }}</p>
        </div>

        {{-- Filter controls narrow the provider results before rendering. --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <p class="text-sm font-semibold text-gray-900">{{ __('Filters') }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ __('Narrow the dataset before it is exposed to the module template.') }}</p>

            <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-4">
                <div class="rounded-md border border-gray-200 bg-white p-4">
                    <x-input-label :value="__('Content Owner')" />
                    <select
                        name="settings_authored_by"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                        x-model="providerSettings.authoredBy"
                    >
                        <option value="all">{{ __('All content') }}</option>
                        <option value="viewer">{{ __('Current user only') }}</option>
                    </select>
                    <p class="mt-2 text-sm text-gray-500">{{ __('Use current user only for personal dashboard widgets like task lists or author-specific content summaries.') }}</p>
                </div>

                <div class="rounded-md border border-gray-200 bg-white p-4">
                    <x-multi-select-picker
                        :label="__('Content Type')"
                        input-name="settings_type_names[]"
                        :items="$pickers['typeItems']"
                        :selected="$settingsUi['selectedTypeNames']"
                        x-effect="syncSelectedIds(providerSettings.typeNames)"
                        :placeholder="__('Type')"
                        :no-results-text="__('No content types available to add.')"
                        :add-aria-label="__('Add content type filter')"
                        :remove-aria-label="__('Remove content type filter')"
                        compact
                        x-on:multi-select-picker:change="
                            if ($event.detail.inputName === 'settings_type_names[]') {
                                providerSettings.typeNames = Array.isArray($event.detail.selectedIds)
                                    ? $event.detail.selectedIds
                                    : [];
                                notifyGuidedSettingsChanged();
                            }
                        "
                    />
                    <p class="mt-2 text-sm text-gray-500">{{ __('Optional. Only matching content types will be returned.') }}</p>
                </div>

                <div class="rounded-md border border-gray-200 bg-white p-4">
                    <x-multi-select-picker
                        :label="__('Status')"
                        input-name="settings_status_names[]"
                        :items="$pickers['statusItems']"
                        :selected="$settingsUi['selectedStatusNames']"
                        x-effect="syncSelectedIds(providerSettings.statusNames)"
                        :placeholder="__('Status')"
                        :no-results-text="__('No statuses available to add.')"
                        :add-aria-label="__('Add status filter')"
                        :remove-aria-label="__('Remove status filter')"
                        compact
                        x-on:multi-select-picker:change="
                            if ($event.detail.inputName === 'settings_status_names[]') {
                                providerSettings.statusNames = Array.isArray($event.detail.selectedIds)
                                    ? $event.detail.selectedIds
                                    : [];
                                notifyGuidedSettingsChanged();
                            }
                        "
                    />
                    <p class="mt-2 text-sm text-gray-500">{{ __('Recommended: keep Published selected unless the module intentionally surfaces drafts.') }}</p>
                </div>

                <div class="rounded-md border border-gray-200 bg-white p-4">
                    <x-multi-select-picker
                        :label="__('Tags')"
                        input-name="settings_tag_ids[]"
                        :items="$pickers['tagItems']"
                        :selected="collect($settingsUi['selectedTagIds'])->map(static fn (int $id): string => (string) $id)->all()"
                        x-effect="syncSelectedIds(providerSettings.tagIds.map((id) => String(id)))"
                        :placeholder="__('Tag')"
                        :no-results-text="__('No tags available to add.')"
                        :add-aria-label="__('Add tag filter')"
                        :remove-aria-label="__('Remove tag filter')"
                        compact
                        x-on:multi-select-picker:change="
                            if ($event.detail.inputName === 'settings_tag_ids[]') {
                                providerSettings.tagIds = Array.isArray($event.detail.selectedIds)
                                    ? $event.detail.selectedIds
                                        .map((id) => Number.parseInt(String(id), 10))
                                        .filter((id) => Number.isInteger(id) && id > 0)
                                    : [];
                                notifyGuidedSettingsChanged();
                            }
                        "
                    />
                    <p class="mt-2 text-sm text-gray-500">{{ __('Optional. Keeps only content that has at least one selected tag.') }}</p>
                </div>
            </div>
        </div>

        {{-- Sorting and limit controls shape the final result set exposed to the template. --}}
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div>
                <x-input-label :value="__('Sort Field')" />
                <select
                    name="settings_order_by"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                    x-model="providerSettings.orderBy"
                >
                    <option value="published_at">{{ __('Published Date') }}</option>
                    <option value="created_at">{{ __('Created Date') }}</option>
                    <option value="priority">{{ __('Priority') }}</option>
                    <option value="name">{{ __('Name') }}</option>
                </select>
                <p class="mt-2 text-sm text-gray-500">{{ __('Choose which field orders the final result set.') }}</p>
            </div>

            <div>
                <x-input-label :value="__('Sort Direction')" />
                <select
                    name="settings_order_direction"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                    x-model="providerSettings.orderDirection"
                >
                    <option value="desc">{{ __('Descending') }}</option>
                    <option value="asc">{{ __('Ascending') }}</option>
                </select>
                <p class="mt-2 text-sm text-gray-500">{{ __('Controls whether higher/newer values appear first or last.') }}</p>
            </div>

            <div>
                <x-input-label :value="__('Item Limit')" />
                <input
                    name="settings_limit"
                    type="number"
                    min="1"
                    step="1"
                    class="mt-1 block w-full rounded-md border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#077CB6] focus:ring-[#077CB6]"
                    x-model.number="providerSettings.limit"
                >
                <p class="mt-2 text-sm text-gray-500">{{ __('Limits how many items are exposed to the template as') }} <code>@{{ items }}</code>.</p>
            </div>
        </div>

        {{-- Generated provider JSON snapshot for authors who want to inspect the exact payload. --}}
        <x-services.modules.settings-json-preview
            :title="__('View Generated Provider JSON')"
            :description="__('This is the exact data_source payload submitted while guided mode is active.')"
            preview="submittedDataSource()"
        />
    </div>
</x-services.modules.settings-accordion>
