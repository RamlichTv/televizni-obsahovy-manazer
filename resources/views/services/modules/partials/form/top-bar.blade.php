{{--
    Editor top bar.
    Keeps the app-style primary controls available without reusing the standard page header shell.
--}}

<header class="border-b border-gray-200 bg-white shadow-sm">
    <div class="flex min-h-16 flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        {{-- Left side keeps navigation, module identity, category, and active state together. --}}
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3 lg:gap-4">
            <a
                href="{{ route('dashboard') }}"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                aria-label="{{ __('Back to modules') }}"
            >
                <x-application-logo class="h-6 w-auto" :text="false" />
            </a>

            <a
                href="{{ route('modules.index') }}"
                class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
            >
                <x-icon name="arrow" class="h-4 w-4 rotate-180" />
                {{ __('Modules') }}
            </a>

            <div class="min-w-[16rem] flex-1 lg:max-w-2xl">
                <label for="name" class="sr-only">{{ __('Module name') }}</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    class="w-full rounded-md border-gray-300 px-4 py-2.5 text-lg font-semibold text-gray-900 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                    value="{{ old('name', $module->name) }}"
                    placeholder="{{ __('Untitled module') }}"
                    required
                    @readonly($flags['embeddedAccessLocked'])
                >
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div class="min-w-56">
                <label for="category" class="sr-only">{{ __('Category') }}</label>
                <select
                    id="category"
                    name="category"
                    class="w-full rounded-md border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                    required
                    @disabled($flags['embeddedAccessLocked'])
                >
                    <option value="">{{ __('Select category') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category', $module->category) === (string) $category->id)>
                            {{ ucfirst((string) $category->name) }}
                            @if ($category->is_widget)
                                {{ __('(Widget)') }}
                            @endif
                            @if ($category->is_screensaver)
                                {{ __('(Screensaver)') }}
                            @endif
                            @if ($category->allow_embedded)
                                {{ __('(Embedded)') }}
                            @endif
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('category')" />
            </div>

            <label class="inline-flex shrink-0 items-center gap-3 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm">
                <span class="font-medium">{{ __('Active') }}</span>
                <span class="relative inline-flex h-6 w-11 items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input
                        id="is_active"
                        name="is_active"
                        type="checkbox"
                        value="1"
                        class="peer sr-only"
                        @checked((bool) old('is_active', $module->is_active ?? true))
                        @disabled($flags['embeddedAccessLocked'])
                    >
                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#077CB6] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                </span>
            </label>
        </div>

        {{-- Right side keeps layout controls, preview action, destructive action, and save action. --}}
        <div class="flex flex-wrap items-center justify-end gap-2 lg:gap-3">
            @if ($flags['isEdit'])
                <span class="rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-gray-500 shadow-sm">
                    {{ __('Revision') }} {{ $revisions['selected'] }}
                    @if ($revisions['selected'] !== (int) $module->revision)
                        / {{ $module->revision }}
                    @endif
                </span>
            @endif

            <div class="inline-flex items-center rounded-md border border-gray-300 bg-white p-1 shadow-sm">
                <button
                    type="button"
                    @click="setLayout('vertical')"
                    :class="layoutButtonClass('vertical')"
                    class="rounded-md px-3 py-2 text-sm transition"
                    aria-label="{{ __('Vertical split') }}"
                    title="{{ __('Vertical split') }}"
                >
                    <span class="flex items-center gap-1.5">
                        <span class="h-3.5 w-3 rounded-sm border border-current"></span>
                        <span class="h-3.5 w-5 rounded-sm border border-current"></span>
                    </span>
                </button>
                <button
                    type="button"
                    @click="setLayout('horizontal')"
                    :class="layoutButtonClass('horizontal')"
                    class="rounded-md px-3 py-2 text-sm transition"
                    aria-label="{{ __('Horizontal split') }}"
                    title="{{ __('Horizontal split') }}"
                >
                    <span class="grid gap-1">
                        <span class="h-2 w-6 rounded-sm border border-current"></span>
                        <span class="h-2 w-6 rounded-sm border border-current"></span>
                    </span>
                </button>
                <button
                    type="button"
                    @click="setLayout('preview')"
                    :class="layoutButtonClass('preview')"
                    class="rounded-md px-3 py-2 text-sm transition"
                    aria-label="{{ __('Preview only') }}"
                    title="{{ __('Preview only') }}"
                >
                    <span class="h-3.5 w-6 rounded-sm border border-current"></span>
                </button>
            </div>

            <button
                type="button"
                @click.prevent="renderDraftPreview()"
                :disabled="@js((bool) $flags['embeddedAccessLocked']) || isPreviewLoading"
                class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <svg x-show="!isPreviewLoading" x-cloak class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6 4.75A.75.75 0 0 1 7.132 4.1l7 5a.75.75 0 0 1 0 1.22l-7 5A.75.75 0 0 1 6 14.7V4.75Z" />
                </svg>
                <svg x-show="isPreviewLoading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-20"></circle>
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                </svg>
                <span x-text="isPreviewLoading ? '{{ __('Rendering...') }}' : '{{ __('Render Preview') }}'"></span>
            </button>

            @if ($flags['isEdit'])
                @can('delete', $module)
                    <button
                        type="submit"
                        form="module-delete-form"
                        class="inline-flex items-center gap-2 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-red-700 shadow-sm transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-200 focus:ring-offset-2"
                    >
                        <x-icon name="trash" class="h-4 w-4" />
                        {{ __('Delete') }}
                    </button>
                @endcan
            @endif

            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-md bg-[#077cb6] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition hover:bg-[#E69C24] focus:bg-[#D78500] focus:outline-none focus:ring-2 focus:ring-[#D78500] focus:ring-offset-2"
                @disabled($flags['embeddedAccessLocked'])
            >
                {{ $submitLabel }}
            </button>
        </div>
    </div>
</header>
