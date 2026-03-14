{{--
    Content-type manager partial.
    It combines a searchable type table and a create form inside the shared light modal shell.
--}}

@php
    /**
     * Scope validation feedback to this manager and restore the edited row when an update fails.
     */
    $contentTypeManagerErrors = $errors->getBag('contentTypeManager');
    $restoredEditingTypeId = is_numeric(old('editing_type_id')) ? (int) old('editing_type_id') : null;
    $isCreateValidationContext = $contentTypeManagerErrors->isNotEmpty()
        && old('return_modal') === 'types'
        && $restoredEditingTypeId === null;
@endphp

<div class="space-y-4">
    {{-- Validation summary explains why the manager reopened after a failed save. --}}
    @if ($contentTypeManagerErrors->isNotEmpty())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $contentTypeManagerErrors->first() }}
        </div>
    @endif

    <div
        class="flex h-full min-h-0 flex-col gap-6 overflow-hidden lg:flex-row"
        x-data="{
            editingTypeId: @js($restoredEditingTypeId),
            searchQuery: '',
            types: @js($contentTypes->map(fn ($type) => [
                'id' => $type->id,
                'name' => strtolower($type->name),
                'extensions' => strtolower(implode(', ', $type->extension_whitelist ?? [])),
            ])->values()),
            matchesType(type) {
                const query = this.searchQuery.toLowerCase().trim();
                return query === '' || type.name.includes(query) || type.extensions.includes(query);
            },
            filteredTypesCount() {
                return this.types.filter(type => this.matchesType(type)).length;
            },
            isVisibleType(typeId) {
                const type = this.types.find(item => item.id === typeId);
                return type ? this.matchesType(type) : true;
            },
            toggleEditor(typeId) {
                this.editingTypeId = this.editingTypeId === typeId ? null : typeId;
            },
        }"
    >
        {{-- Existing type table stays scrollable inside the modal body. --}}
        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-lg font-bold text-gray-700">{{ __('Existing Types') }}</h3>
            <div class="w-full sm:max-w-xs">
                <label for="content_type_search" class="sr-only">{{ __('Search content types') }}</label>
                <input
                    id="content_type_search"
                    type="text"
                    x-model.debounce.150ms="searchQuery"
                    placeholder="{{ __('Search types or filetypes...') }}"
                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                >
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full text-left text-sm text-gray-500">
                <thead class="sticky top-0 z-10 bg-gray-100 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="px-4 py-2">{{ __('Name') }}</th>
                        <th class="px-4 py-2">{{ __('Allowed filetypes') }}</th>
                        <th class="px-4 py-2">{{ __('Max Size') }}</th>
                        <th class="px-4 py-2">{{ __('Multiple Files') }}</th>
                        <th class="px-4 py-2">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if($contentTypes->isNotEmpty())
                        <tr x-show="filteredTypesCount() === 0" x-cloak class="border-b bg-white">
                            <td colspan="5" class="px-4 py-4 text-center text-sm italic text-gray-500">
                                {{ __('No content types match your search.') }}
                            </td>
                        </tr>
                    @endif

                    @forelse($contentTypes as $type)
                        @php
                            /**
                             * Only the form that triggered validation should be repopulated with old input.
                             */
                            $isRestoredType = $restoredEditingTypeId === $type->id;
                            $typeNameValue = $isRestoredType ? old('name', $type->name) : $type->name;
                            $typeExtensionsValue = $isRestoredType
                                ? old('extensions', implode(', ', $type->extension_whitelist ?? []))
                                : implode(', ', $type->extension_whitelist ?? []);
                            $typeMaxSizeValue = $isRestoredType
                                ? old('max_size_mb', $type->max_size ? round($type->max_size / 1024 / 1024) : '')
                                : ($type->max_size ? round($type->max_size / 1024 / 1024) : '');
                        @endphp

                        {{-- Summary row stays visible while the inline editor opens beneath it. --}}
                        <tr x-show="isVisibleType({{ $type->id }})" x-cloak class="border-b bg-white hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium text-gray-900">{{ $type->name }}</td>
                            <td class="px-4 py-2">{{ implode(', ', $type->extension_whitelist ?? []) }}</td>
                            <td class="px-4 py-2">
                                {{ $type->max_size ? round($type->max_size / 1024 / 1024) . ' MB' : '—' }}
                            </td>
                            <td class="px-4 py-2">
                                @if($type->is_file)
                                    {{ $type->multiple_file ? __('Yes') : __('No') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="text-blue-600 hover:underline" @click="toggleEditor({{ $type->id }})">
                                        <span x-show="editingTypeId !== {{ $type->id }}">{{ __('Edit') }}</span>
                                        <span x-show="editingTypeId === {{ $type->id }}">{{ __('Close') }}</span>
                                    </button>

                                    {{-- Deletion routes through the shared confirm modal while preserving reopen context. --}}
                                    <form
                                        action="{{ route('content.types.destroy', $type) }}"
                                        method="POST"
                                        data-confirm-title="{{ __('Delete content type') }}"
                                        data-confirm-message="{{ __('Delete :name? Content associated with this type might break.', ['name' => $type->name]) }}"
                                        data-confirm-label="{{ __('Delete') }}"
                                        data-cancel-label="{{ __('Cancel') }}"
                                        data-confirm-variant="danger"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="return_modal" value="types">
                                        <button class="text-red-500 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Inline editor preserves row context when validation returns from the controller. --}}
                        <tr x-show="editingTypeId === {{ $type->id }} && isVisibleType({{ $type->id }})" x-cloak class="border-b bg-gray-50">
                            <td colspan="5" class="px-4 py-4">
                                <form
                                    action="{{ route('content.types.update', $type) }}"
                                    method="POST"
                                    class="space-y-3"
                                    x-data="{
                                        isFile: @js($isRestoredType ? (bool) old('is_file', $type->is_file) : (bool) $type->is_file),
                                        isMultipleFile: @js($isRestoredType ? (bool) old('multiple_file', $type->multiple_file) : (bool) $type->multiple_file),
                                    }"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="return_modal" value="types">
                                    <input type="hidden" name="editing_type_id" value="{{ $type->id }}">

                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="text-xs font-bold uppercase text-gray-500">{{ __('Name') }}</label>
                                            <input
                                                type="text"
                                                name="name"
                                                value="{{ $typeNameValue }}"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                                required
                                            >
                                            @if ($isRestoredType)
                                                <x-input-error :messages="$contentTypeManagerErrors->get('name')" class="mt-2" />
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2 pt-6">
                                            <input type="hidden" name="is_file" value="0">
                                            <input
                                                type="checkbox"
                                                id="is_file_{{ $type->id }}"
                                                name="is_file"
                                                value="1"
                                                x-model="isFile"
                                                @change="if (!isFile) { isMultipleFile = false; }"
                                                class="rounded border-gray-300 text-[#077CB6] shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                            >
                                            <label for="is_file_{{ $type->id }}" class="cursor-pointer select-none text-sm font-medium text-gray-700">
                                                {{ __('Is this a file?') }}
                                            </label>
                                        </div>
                                    </div>

                                    <div x-show="isFile" class="flex items-center gap-2">
                                        <input type="hidden" name="multiple_file" value="0">
                                        <input
                                            type="checkbox"
                                            id="multiple_file_{{ $type->id }}"
                                            name="multiple_file"
                                            value="1"
                                            x-model="isMultipleFile"
                                            class="rounded border-gray-300 text-[#077CB6] shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                        >
                                        <label for="multiple_file_{{ $type->id }}" class="cursor-pointer select-none text-sm font-medium text-gray-700">
                                            {{ __('Allow multiple files') }}
                                        </label>
                                    </div>

                                    <div x-show="isFile" x-transition class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="text-xs font-bold uppercase text-gray-500">{{ __('Extensions or MIME (comma separated)') }}</label>
                                            <input
                                                type="text"
                                                name="extensions"
                                                value="{{ $typeExtensionsValue }}"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                                :required="isFile"
                                            >
                                            @if ($isRestoredType)
                                                <x-input-error :messages="$contentTypeManagerErrors->get('extensions')" class="mt-2" />
                                            @endif
                                        </div>

                                        <div>
                                            <label class="text-xs font-bold uppercase text-gray-500">{{ __('Max Size (MB)') }}</label>
                                            <input
                                                type="number"
                                                name="max_size_mb"
                                                value="{{ $typeMaxSizeValue }}"
                                                min="1"
                                                max="1024"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                                :required="isFile"
                                            >
                                            @if ($isRestoredType)
                                                <x-input-error :messages="$contentTypeManagerErrors->get('max_size_mb')" class="mt-2" />
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <x-primary-button>
                                            {{ __('Update Type') }}
                                        </x-primary-button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">{{ __('No types defined.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>

        {{-- Create panel hides while an inline editor is open so the workspace stays focused. --}}
        <div x-show="!editingTypeId" class="shrink-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm lg:w-80">
            <h3 class="mb-3 text-lg font-bold text-gray-700">{{ __('Add Content Type') }}</h3>

            <form
                action="{{ route('content.types.store') }}"
                method="POST"
                class="space-y-3"
                x-data="{ isFile: @js($isCreateValidationContext ? (bool) old('is_file', false) : false), isMultipleFile: @js($isCreateValidationContext ? (bool) old('multiple_file', false) : false) }"
            >
                @csrf
                <input type="hidden" name="return_modal" value="types">

                <div>
                    <label class="text-xs font-bold uppercase text-gray-500">{{ __('Name') }}</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ $isCreateValidationContext ? old('name') : '' }}"
                        placeholder="{{ __('e.g. Animation') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                        required
                    >
                    <x-input-error :messages="$isCreateValidationContext ? $contentTypeManagerErrors->get('name') : []" class="mt-2" />
                </div>

                <div class="flex items-center gap-2 py-2">
                    <input
                        type="checkbox"
                        id="is_file"
                        name="is_file"
                        value="1"
                        x-model="isFile"
                        @change="if (!isFile) { isMultipleFile = false; }"
                        class="rounded border-gray-300 text-[#077CB6] shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                    >
                    <label for="is_file" class="cursor-pointer select-none text-sm font-medium text-gray-700">
                        {{ __('Is this a file?') }}
                    </label>
                </div>

                <div x-show="isFile" class="flex items-center gap-2">
                    <input type="hidden" name="multiple_file" value="0">
                    <input
                        type="checkbox"
                        id="multiple_file"
                        name="multiple_file"
                        value="1"
                        x-model="isMultipleFile"
                        class="rounded border-gray-300 text-[#077CB6] shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                    >
                    <label for="multiple_file" class="cursor-pointer select-none text-sm font-medium text-gray-700">
                        {{ __('Allow multiple files') }}
                    </label>
                </div>

                <div x-show="isFile" x-transition class="space-y-3">
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-500">{{ __('Extensions or MIME (comma separated)') }}</label>
                        <input
                            type="text"
                            name="extensions"
                            value="{{ $isCreateValidationContext ? old('extensions') : '' }}"
                            placeholder="{{ __('json, lottie, gif') }}"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                            :required="isFile"
                        >
                        <x-input-error :messages="$isCreateValidationContext ? $contentTypeManagerErrors->get('extensions') : []" class="mt-2" />
                    </div>

                    <div>
                        <label class="text-xs font-bold uppercase text-gray-500">{{ __('Max Size (MB)') }}</label>
                        <input
                            type="number"
                            name="max_size_mb"
                            value="{{ $isCreateValidationContext ? old('max_size_mb') : '' }}"
                            placeholder="10"
                            min="1"
                            max="1024"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                            :required="isFile"
                        >
                        <x-input-error :messages="$isCreateValidationContext ? $contentTypeManagerErrors->get('max_size_mb') : []" class="mt-2" />
                    </div>
                </div>

                <x-primary-button class="w-full justify-center">
                    {{ __('Create Type') }}
                </x-primary-button>
            </form>
        </div>
    </div>
</div>
