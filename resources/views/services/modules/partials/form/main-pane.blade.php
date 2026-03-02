{{--
    Main pane of the module editor.
    Left side stays focused on module identity, preview rendering, and save actions.
--}}

{{-- Main workspace: overview, rendered preview, and submit actions --}}
<div class="flex-1 overflow-y-auto bg-gray-50 p-4 md:p-6 space-y-5">

    {{-- Module identity and lifecycle controls --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 md:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('Module Overview') }}</h2>

            <button
                type="button"
                @click="sidebarOpen = !sidebarOpen"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
            >
                <span x-text="sidebarOpen ? '{{ __('Hide Sidebar') }}' : '{{ __('Show Sidebar') }}'"></span>
            </button>
        </div>

        {{-- Base module fields --}}
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('Name')" :required="true"/>
                <x-text-input
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('name', $module->name)"
                    required
                />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="category" :value="__('Category')" :required="true"/>
                <select
                    id="category"
                    name="category"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                    required
                >
                    <option value="">{{ __('Select category') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category', $module->category) === (string) $category->id)>
                            {{ ucfirst($category->name) }}
                            @if ($category->is_widget)
                                {{ __('(Widget)') }}
                            @endif
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('category')" />
            </div>
        </div>

        {{-- Active flag --}}
        <label for="is_active" class="mt-4 flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-4 cursor-pointer">
            <div>
                <p class="text-sm font-semibold text-gray-900">{{ __('Active module') }}</p>
                <p class="text-xs text-gray-500">{{ __('Inactive modules stay in catalog but are not rendered.') }}</p>
            </div>

            <div class="relative inline-flex h-6 w-11 shrink-0 items-center">
                <input type="hidden" name="is_active" value="0">
                <input
                    id="is_active"
                    name="is_active"
                    type="checkbox"
                    value="1"
                    class="peer sr-only"
                    @checked((bool) old('is_active', $module->is_active ?? true))
                >
                <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#077CB6] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
            </div>
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />

        @if ($isEdit)
        
            {{-- Stored revision/file metadata and revision switcher --}}
            <div class="mt-4 grid grid-cols-1 gap-3 text-xs text-gray-600 rounded-lg border border-gray-200 bg-gray-50 p-4 md:grid-cols-2">
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Revision') }}</p>
                    <p>{{ $module->revision }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Template Path') }}</p>
                    <p class="break-all">{{ $module->template_path ?: __('Not generated yet') }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Style Path') }}</p>
                    <p class="break-all">{{ $module->style_path ?: __('Not generated yet') }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ __('Script Path') }}</p>
                    <p class="break-all">{{ $module->script_path ?: __('Not generated yet') }}</p>
                </div>
                @if (count($resolvedAvailableRevisions) > 1)
                    <div class="md:col-span-2">
                        <p class="font-semibold text-gray-900">{{ __('Browse Revision') }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-3">
                            <select
                                class="block w-full max-w-xs rounded-md border-gray-300 text-xs shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                onchange="window.location.href = this.value"
                            >
                                @foreach ($resolvedAvailableRevisions as $revisionOption)
                                    <option
                                        value="{{ route('module.edit', ['module' => $module, 'revision' => $revisionOption]) }}"
                                        @selected($resolvedSelectedRevision === $revisionOption)
                                    >
                                        {{ __('Revision') }} {{ $revisionOption }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($resolvedSelectedRevision !== (int) $module->revision)
                                <p class="text-amber-700">{{ __('You are viewing an older revision snapshot.') }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Draft render area --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 md:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">{{ __('Preview') }}</h3>
                <p class="text-xs text-gray-500">
                    {{ __('Render output from current editor values.') }}
                </p>
            </div>

            <button
                type="submit"
                formaction="{{ route('module.preview') }}"
                formmethod="POST"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                {{ __('Render Draft Preview') }}
            </button>
        </div>

        <div class="mt-4 min-h-[56vh] grid place-items-center rounded-lg border border-gray-200 bg-white p-5 overflow-auto">
            @if (is_string($resolvedPreviewHtml) && trim($resolvedPreviewHtml) !== '')
                {!! $resolvedPreviewHtml !!}
            @else
                <div class="grid size-full place-items-center rounded-lg border border-dashed border-gray-300 bg-gray-50">
                    <p class="px-4 text-center text-sm text-gray-500">
                        {{ __('Preview appears here after saving or rendering draft preview.') }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Final actions --}}
    <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-5">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <x-secondary-link href="{{ route('modules.index') }}">
            {{ __('Cancel') }}
        </x-secondary-link>
    </div>
</div>
