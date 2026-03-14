{{--
    Category management sidebar used on modules index.
    Handles create, edit, and delete flows without duplicating forms in the page view.
--}}

@props([
    'categories',
    'canManageCategories' => false,
    'canManageEmbeddedModules' => false,
    'isAdminUser' => false,
])

<aside
    id="module-categories-sidebar"
    class="fixed inset-y-0 left-0 z-40 w-full max-w-sm -translate-x-full space-y-4 overflow-y-auto bg-gray-100 p-4 shadow-xl transition-transform duration-200 ease-out lg:static lg:col-span-1 lg:max-w-none lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0 lg:shadow-none"
    :class="categorySidebarOpen ? 'translate-x-0' : ''"
>
    {{-- Mobile-only header and close action --}}
    <div class="flex items-center justify-between lg:hidden">
        <h2 class="text-sm font-semibold text-gray-900">{{ __('Categories') }}</h2>
        <button
            type="button"
            class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-50"
            @click="categorySidebarOpen = false"
        >
            {{ __('Close') }}
        </button>
    </div>

    @if ($canManageCategories)
        {{-- Category creation form --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('Create Category') }}</h2>
            <form method="POST" action="{{ route('module.categories.store') }}" class="mt-3 space-y-3">
                @csrf
                <div>
                    <x-input-label for="new_category_name" :value="__('Name')" :required="true"/>
                    <x-text-input id="new_category_name" name="name" type="text" class="mt-1 block w-full" required />
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="hidden" name="is_widget" value="0">
                    <input type="checkbox" name="is_widget" value="1" class="rounded border-gray-300 text-[#077CB6] focus:ring-[#077CB6]">
                    {{ __('Widget Category') }}
                </label>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="hidden" name="is_screensaver" value="0">
                    <input type="checkbox" name="is_screensaver" value="1" class="rounded border-gray-300 text-[#077CB6] focus:ring-[#077CB6]">
                    {{ __('Screensaver Category') }}
                </label>

                @if ($canManageEmbeddedModules)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="allow_embedded" value="0">
                        <input type="checkbox" name="allow_embedded" value="1" class="rounded border-gray-300 text-[#077CB6] focus:ring-[#077CB6]">
                        {{ __('Allow Embedded (iframe)') }}
                    </label>
                @endif

                @if ($isAdminUser)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_system" value="0">
                        <input type="checkbox" name="is_system" value="1" class="rounded border-gray-300 text-[#077CB6] focus:ring-[#077CB6]">
                        {{ __('System Category') }}
                    </label>
                @endif

                <x-primary-button class="w-full justify-center">{{ __('Add Category') }}</x-primary-button>
            </form>
        </div>
    @endif

    {{-- Existing category list with inline edit state per item --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4" x-data="{ listOpen: true }">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-2 text-left"
            @click="listOpen = !listOpen"
            :aria-expanded="listOpen ? 'true' : 'false'"
        >
            <h2 class="text-sm font-semibold text-gray-900">{{ __('Manage Categories') }}</h2>
            <span class="text-xs font-medium text-gray-500" x-text="listOpen ? @js(__('Hide')) : @js(__('Show'))"></span>
        </button>

        <div
            x-cloak
            x-show="listOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="mt-3 max-h-112 space-y-3 overflow-y-auto pr-1"
        >
            @foreach ($categories as $category)
                @php
                    $isSystemCategory = (bool) $category->is_system;
                    $canEditCategory = $canManageCategories && (!$isSystemCategory || $isAdminUser);
                    $canDeleteCategory = $canManageCategories && (!$isSystemCategory || $isAdminUser) && (int) $category->modules_count === 0;
                @endphp

                <div class="rounded-lg border border-gray-200 p-3" x-data="{ editing: false }">
                    {{-- Category summary row --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $category->name }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px]">
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-600">
                                    {{ __('Modules:') }} {{ $category->modules_count }}
                                </span>
                                @if ($category->is_widget)
                                    <span class="rounded bg-sky-100 px-2 py-0.5 text-sky-800">{{ __('Widget') }}</span>
                                @endif
                                @if ($category->is_screensaver)
                                    <span class="rounded bg-indigo-100 px-2 py-0.5 text-indigo-800">{{ __('Screensaver') }}</span>
                                @endif
                                @if ($category->allow_embedded)
                                    <span class="rounded bg-emerald-100 px-2 py-0.5 text-emerald-800">{{ __('Embedded') }}</span>
                                @endif
                                @if ($isSystemCategory)
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700">{{ __('System') }}</span>
                                @endif
                            </div>
                        </div>

                        @if ($canEditCategory)
                            <button
                                type="button"
                                @click="editing = !editing"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50"
                                :aria-expanded="editing ? 'true' : 'false'"
                            >
                                <x-icon name="pen" class="h-4 w-4" />
                            </button>
                        @endif
                    </div>

                    @if ($isSystemCategory && !$isAdminUser)
                        <p class="mt-2 text-xs text-gray-600">
                            {{ __('System categories are locked and cannot be edited or deleted.') }}
                        </p>
                    @endif

                    @if ($canEditCategory)
                        {{-- Inline edit and delete controls --}}
                        <div
                            x-cloak
                            x-show="editing"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="mt-3 space-y-3 border-t border-gray-200 pt-3"
                        >
                            <form method="POST" action="{{ route('module.categories.update', ['category' => $category]) }}" class="space-y-3">
                                @csrf
                                @method('PATCH')

                                <div>
                                    <x-input-label :value="__('Name')" :required="true"/>
                                    <x-text-input
                                        name="name"
                                        type="text"
                                        class="mt-1 block w-full text-sm"
                                        :value="$category->name"
                                        required
                                    />
                                </div>

                                <label class="flex items-center gap-2 text-xs text-gray-600">
                                    <input type="hidden" name="is_widget" value="0">
                                    <input
                                        type="checkbox"
                                        name="is_widget"
                                        value="1"
                                        class="rounded border-gray-300 text-[#077CB6] focus:ring-[#077CB6]"
                                        @checked($category->is_widget)
                                    >
                                    {{ __('Widget') }}
                                </label>

                                <label class="flex items-center gap-2 text-xs text-gray-600">
                                    <input type="hidden" name="is_screensaver" value="0">
                                    <input
                                        type="checkbox"
                                        name="is_screensaver"
                                        value="1"
                                        class="rounded border-gray-300 text-[#077CB6] focus:ring-[#077CB6]"
                                        @checked($category->is_screensaver)
                                    >
                                    {{ __('Screensaver') }}
                                </label>

                                @if ($canManageEmbeddedModules)
                                    <label class="flex items-center gap-2 text-xs text-gray-600">
                                        <input type="hidden" name="allow_embedded" value="0">
                                        <input
                                            type="checkbox"
                                            name="allow_embedded"
                                            value="1"
                                            class="rounded border-gray-300 text-[#077CB6] focus:ring-[#077CB6]"
                                            @checked($category->allow_embedded)
                                        >
                                        {{ __('Allow Embedded (iframe)') }}
                                    </label>
                                @endif

                                @if ($isAdminUser)
                                    <label class="flex items-center gap-2 text-xs text-gray-600">
                                        <input type="hidden" name="is_system" value="0">
                                        <input
                                            type="checkbox"
                                            name="is_system"
                                            value="1"
                                            class="rounded border-gray-300 text-[#077CB6] focus:ring-[#077CB6]"
                                            @checked($category->is_system)
                                        >
                                        {{ __('System') }}
                                    </label>
                                @endif

                                <div class="flex items-center gap-2">
                                    <button
                                        type="submit"
                                        class="rounded-md border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        {{ __('Save') }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="editing = false"
                                        class="rounded-md border border-gray-200 px-2 py-1 text-xs font-semibold text-gray-500 hover:bg-gray-50"
                                    >
                                        {{ __('Cancel') }}
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('module.categories.destroy', ['category' => $category]) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="w-full rounded-md border border-red-200 bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50"
                                    @disabled(!$canDeleteCategory)
                                >
                                    {{ __('Delete') }}
                                </button>
                            </form>

                            @if ((int) $category->modules_count > 0)
                                <p class="text-xs text-gray-600">
                                    {{ __('Remove modules from this category before deleting it.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</aside>
