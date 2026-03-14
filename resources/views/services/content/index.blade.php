{{--
    Content services landing page.
    It mounts the shared light-mode modal shell for both tag and content-type management flows.
--}}

<x-app-layout title="{{ __('Content') }}">
    @php
        /**
         * Reopen the correct manager after redirects or validation failures.
         */
        $openContentManager = session('openContentManager');
        $tagManagerErrors = $errors->getBag('tagManager');
        $contentTypeManagerErrors = $errors->getBag('contentTypeManager');
        $tagManagerShouldOpen = $tagManagerErrors->isNotEmpty() || old('return_modal') === 'tags' || $openContentManager === 'tags';
        $typeManagerShouldOpen = $contentTypeManagerErrors->isNotEmpty() || old('return_modal') === 'types' || $openContentManager === 'types';
    @endphp

    {{-- Header actions keep local Alpine modal state so these managers do not depend on the shared named-modal event bus. --}}
    <x-slot name="header">
        <div
            class="w-full"
            x-data="{
                tagModalOpen: @js($tagManagerShouldOpen),
                typeModalOpen: @js($typeManagerShouldOpen),
            }"
            @keydown.escape.window="
                if (typeModalOpen) {
                    typeModalOpen = false;
                } else if (tagModalOpen) {
                    tagModalOpen = false;
                }
            "
        >
            <div class="flex items-start justify-between gap-4">
                <x-services.page-header
                    title="{{ __('Content Services') }}"
                    description="{{ __('Browse content collections and manage types or tags from one place.') }}"
                />

                <div class="flex items-center gap-2 whitespace-nowrap">
                    @can('manage_tags')
                        <x-secondary-button type="button" @click="tagModalOpen = true">
                            <x-icon name="hashtag" class="w-4 h-4 mr-2"/>
                            {{ __('Tags') }}
                        </x-secondary-button>
                    @endcan

                    @can('manage_content_types')
                        <x-secondary-button type="button" @click="typeModalOpen = true">
                            <x-icon name="min-cog" class="w-4 h-4 mr-2"/>
                            {{ __('Types') }}
                        </x-secondary-button>
                    @endcan
                </div>
            </div>

            {{-- Tag manager dialog uses page-local Alpine state and keeps the same reopen behavior after redirects. --}}
            @can('manage_tags')
                <div x-show="tagModalOpen" x-cloak class="fixed inset-0 z-50">
                    <div
                        x-show="tagModalOpen"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-900/35 backdrop-blur-[1px]"
                        @click="tagModalOpen = false"
                    ></div>

                    <div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-6">
                        <div class="flex min-h-full items-center justify-center">
                            <div
                                x-show="tagModalOpen"
                                x-transition:enter="transform transition ease-out duration-300"
                                x-transition:enter-start="translate-y-4 opacity-0 sm:scale-95"
                                x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                                x-transition:leave="transform transition ease-in duration-200"
                                x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                                x-transition:leave-end="translate-y-4 opacity-0 sm:scale-95"
                                class="flex w-full max-w-4xl max-h-[min(80vh,720px)] min-h-96 flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                            >
                                <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Manage Tags') }}</h3>
                                            <p class="mt-1 text-sm text-gray-500">{{ __('Create, search, and remove reusable content tags without leaving the content landing page.') }}</p>
                                        </div>

                                        <button
                                            type="button"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                                            @click="tagModalOpen = false"
                                        >
                                            <span class="sr-only">{{ __('Close') }}</span>
                                            <x-icon name="x" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="min-h-0 flex-1 overflow-hidden px-5 py-5 sm:px-6">
                                    @include('services.content.partials.tag-manager')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan

            {{-- Content-type manager uses the same local modal pattern with a wider shell for the type table. --}}
            @can('manage_content_types')
                <div x-show="typeModalOpen" x-cloak class="fixed inset-0 z-50">
                    <div
                        x-show="typeModalOpen"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-gray-900/35 backdrop-blur-[1px]"
                        @click="typeModalOpen = false"
                    ></div>

                    <div class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-6">
                        <div class="flex min-h-full items-center justify-center">
                            <div
                                x-show="typeModalOpen"
                                x-transition:enter="transform transition ease-out duration-300"
                                x-transition:enter-start="translate-y-4 opacity-0 sm:scale-95"
                                x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                                x-transition:leave="transform transition ease-in duration-200"
                                x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                                x-transition:leave-end="translate-y-4 opacity-0 sm:scale-95"
                                class="flex w-full max-w-6xl max-h-[min(84vh,860px)] min-h-112 flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                            >
                                <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">{{ __('Manage Content Types') }}</h3>
                                            <p class="mt-1 text-sm text-gray-500">{{ __('Maintain file rules and reusable type definitions in the same light modal system used across the app.') }}</p>
                                        </div>

                                        <button
                                            type="button"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#5EB4EE] focus:ring-offset-2"
                                            @click="typeModalOpen = false"
                                        >
                                            <span class="sr-only">{{ __('Close') }}</span>
                                            <x-icon name="x" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="min-h-0 flex-1 overflow-hidden px-5 py-5 sm:px-6">
                                    @include('services.content.partials.content-type-manager')
                                </div>
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
                title="{{ __('Manage My Content') }}"
                description="{{ __('View and manage content created by me.') }}"
                icon="folder"
                link="{{ route('content.my', Auth::user()->username) }}"
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
