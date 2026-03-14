{{--
    Overview:
    Modules index shell. Detailed sections are rendered by x-services.modules.* components.
--}}

<x-app-layout title="{{ __('Modules') }}">
    <x-slot name="modulesNavTitle">
        {{ __('Modules') }}
    </x-slot>

    <x-slot name="header">
        {{-- Header actions --}}
        <x-services.page-header
            title="{{ __('Module Services') }}"
            description="{{ __('File-backed, sanitized HTML modules with revision history.') }}"
        >
        </x-services.page-header>
        <x-secondary-button
            type="button"
            x-data="{}"
            @click="$dispatch('toggle-module-category-sidebar')"
            class="lg:hidden"
            aria-controls="module-categories-sidebar"
        >
            <x-icon name="cog" class="size-7" />
            {{ __('Manage Categories') }}
        </x-secondary-button>
    </x-slot>

    <section
        class="mx-auto w-full max-w-7xl p-4 lg:p-6"
        x-data="{ categorySidebarOpen: false }"
        @toggle-module-category-sidebar.window="categorySidebarOpen = !categorySidebarOpen"
        @keydown.escape.window="categorySidebarOpen = false"
    >
        @php
            $currentUser = Auth::user();
            $canManageCategories = (bool) ($currentUser?->can('manage_module_categories'));
            $canManageEmbeddedModules = (bool) ($currentUser?->can('manage_embedded_modules'));
            $isAdminUser = (bool) ($currentUser?->isAdmin());
            $selectedCategoryIds = collect((array) ($filters['category_ids'] ?? []))
                ->map(fn ($id) => (string) $id)
                ->filter()
                ->unique()
                ->values();
            $selectedWidgetFlags = collect((array) ($filters['is_widget_flags'] ?? []))
                ->map(fn ($flag) => (string) $flag)
                ->filter(fn ($flag) => in_array($flag, ['0', '1'], true))
                ->unique()
                ->values();
            $categoriesForPicker = $categories->map(fn ($category) => [
                'id' => (string) $category->id,
                'name' => ucfirst((string) $category->name),
            ])->values();
            $widgetFilterOptions = collect([
                ['id' => '1', 'name' => __('Widget')],
                ['id' => '0', 'name' => __('Standard')],
            ]);
        @endphp

        @if (config('modules.tailwind_build.enabled'))
            {{-- Background build state for Tailwind class generation --}}
            <x-services.modules.tailwind-status :tailwind-build="$tailwindBuild" />
        @endif

        @if ($errors->has('module'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first('module') }}
            </div>
        @endif

        @if ($errors->has('category'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first('category') }}
            </div>
        @endif

        {{-- Mobile backdrop for slide-in category sidebar --}}
        <div
            x-cloak
            x-show="categorySidebarOpen"
            class="fixed inset-0 z-30 bg-gray-900/40 lg:hidden"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="categorySidebarOpen = false"
        ></div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            {{-- Category management column --}}
            <x-services.modules.category-sidebar
                :categories="$categories"
                :can-manage-categories="$canManageCategories"
                :can-manage-embedded-modules="$canManageEmbeddedModules"
                :is-admin-user="$isAdminUser"
            />

            <div class="lg:col-span-3">
                
                {{-- Search/filter controls --}}
                <x-filter-panel
                    :search-value="$filters['search'] ?? ''"
                    :search-placeholder="__('Module name...')"
                    :search-suggestions="$searchSuggestions ?? []"
                    :pickers="[
                        [
                            'label' => __('Category'),
                            'input_name' => 'category_ids[]',
                            'items' => $categoriesForPicker,
                            'selected' => $selectedCategoryIds,
                            'placeholder' => __('Category'),
                            'no_selection_text' => __('All categories'),
                            'no_results_text' => __('No categories available to add.'),
                            'add_aria_label' => __('Add category filter'),
                            'remove_aria_label' => __('Remove category filter'),
                        ],
                        [
                            'label' => __('Type'),
                            'input_name' => 'is_widget_flags[]',
                            'items' => $widgetFilterOptions,
                            'selected' => $selectedWidgetFlags,
                            'placeholder' => __('Module type'),
                            'no_selection_text' => __('All module types'),
                            'no_results_text' => __('No module types available to add.'),
                            'add_aria_label' => __('Add module type filter'),
                            'remove_aria_label' => __('Remove module type filter'),
                        ],
                    ]"
                    :reset-url="route('modules.index')"
                />

                {{-- Create card + module listing --}}
                <div class="mt-4 grid grid-cols-2 grid-rows-6 gap-4">
                    @can('create_modules')
                        <x-service-card
                            title="{{ __('Create Module') }}"
                            description="{{ __('Create a new module template for profiles and widgets.') }}"
                            icon="plus-circle"
                            link="{{ route('module.create') }}"
                        />
                    @endcan

                    @forelse ($modules as $module)
                        <x-services.modules.module-card :module="$module" />
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center lg:col-span-2">
                            <h2 class="text-lg font-semibold text-gray-800">{{ __('No modules found') }}</h2>
                            <p class="mt-2 text-sm text-gray-500">{{ __('Try changing filters or create a module.') }}</p>
                        </div>
                    @endforelse
                </div>

                @if ($modules->isNotEmpty())
                    {{-- Pagination --}}
                    <div class="mt-6">
                        {{ $modules->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-app-layout>
