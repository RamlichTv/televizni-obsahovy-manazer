<x-app-layout title="{{ __('Content') }}">
    
    {{--
        We use x-data directly in the header slot.
        The modals are technically inside the header, but visually cover the whole page.
    --}}
    <x-slot name="header">
        <div x-data="{ tagModalOpen: false, typeModalOpen: false }" class="w-full">
            <div class="flex items-start justify-between gap-4">
                <x-services.page-header
                    title="{{ __('Content Services') }}"
                    description="{{ __('Browse content collections and manage types or tags from one place.') }}"
                />

                <div class="flex items-center gap-2 whitespace-nowrap">
                    @can('manage_tags')
                        <x-secondary-button @click="tagModalOpen = true">
                            <x-icon name="hashtag" class="w-4 h-4 mr-2"/>
                            {{ __('Tags') }}
                        </x-secondary-button>
                    @endcan

                    @can('manage_content_types')
                        <x-secondary-button @click="typeModalOpen = true">
                            <x-icon name="min-cog" class="w-4 h-4 mr-2"/>
                            {{ __('Types') }}
                        </x-secondary-button>
                    @endcan
                </div>
            </div>

            {{-- ======================== --}}
            {{-- MODAL: TAG MANAGER       --}}
            {{-- ======================== --}}
            @can('manage_tags')
                <div
                    x-show="tagModalOpen"
                    style="display: none;"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    aria-labelledby="modal-title"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        x-show="tagModalOpen"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-75"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-75"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-500 opacity-75 transition-opacity"
                        @click="tagModalOpen = false"
                    >
                    </div>

                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div
                            x-show="tagModalOpen"
                            x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-75 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-75 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl"
                        >
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100">
                                <div class="sm:flex sm:items-start justify-between">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                        {{ __('Manage Tags') }}
                                    </h3>
                                    <button @click="tagModalOpen = false" class="text-gray-400 hover:text-gray-500">
                                        <span class="sr-only">{{ __('Close') }}</span>
                                        <x-icon name="x" class="w-6 h-6" />
                                    </button>
                                </div>
                            </div>

                            <div class="px-4 py-4 sm:p-6">
                                @include('services.content.partials.tag-manager')
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            {{-- ======================== --}}
            {{-- MODAL: TYPE MANAGER      --}}
            {{-- ======================== --}}
            @can('manage_content_types')
                <div
                    x-show="typeModalOpen"
                    style="display: none;"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    aria-labelledby="modal-title"
                    role="dialog"
                    aria-modal="true"
                >
                    <div
                        x-show="typeModalOpen"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-75"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-75"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-500 opacity-75 transition-opacity"
                        @click="typeModalOpen = false"
                    ></div>

                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div
                            x-show="typeModalOpen"
                            x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-75 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-75 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8"
                        >
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-100">
                                <div class="sm:flex sm:items-start justify-between">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900">
                                        {{ __('Manage Content Types') }}
                                    </h3>
                                    <button @click="typeModalOpen = false" class="text-gray-400 hover:text-gray-500">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="px-4 py-4 sm:p-6">
                                @include('services.content.partials.content-type-manager')
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </x-slot>
    
    {{-- Service entry cards for content workflows. --}}
    <section class="grow grid grid-cols-2 grid-rows-2 lg:grid-cols-5 lg:grid-rows-3 gap-4 p-4 mx-auto w-full">
        @if(Auth::user()->content_objects->count() > 0)
            <x-service-card
                title="{{ __('Manage Content') }}"
                description="{{ __('View and manage existing content.') }}"
                icon="folder"
                link="{{ route('content.manage') }}"
            />
        @endif

        {{-- Highlight unpublished items count for users allowed to publish. --}}
        @can('publish_content')
        <div class="flex justify-end">
            @if (App\Models\ContentObject::where('status_id', 1)->count() > 0)
                <span class="text-white bg-[#077CB6] size-6 m-2 text-center absolute rounded-full">{{ App\Models\ContentObject::where('status_id', 1)->count() }}</span>
            @endif
            <x-service-card
                title="{{ __('Unpublished Content') }}"
                description="{{ __('Unpublished content waiting to be published...') }}"
                icon="invisible"
                link="{{ route('content.unpublished') }}"
                class="flex-1"
            />
        </div>
        @endcan

        <x-service-card
            title="{{ __('Content Library') }}"
            description="{{ __('Browse the content library.') }}"
            icon="library"
            link="{{ route('content.library') }}"
        />

        @can('create_content')
            <x-service-card
                title="{{ __('Create Content') }}"
                description="{{ __('Create new content for the platform.') }}"
                icon="plus-circle"
                link="{{ route('content.create') }}"
            />
        @endcan
    </section>
</x-app-layout>
