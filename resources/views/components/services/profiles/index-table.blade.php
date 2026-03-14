{{--
    Component: Profiles Index Table
    Purpose: Contains list/search/actions for profile records so the page view only coordinates layout.
--}}

@props([
    'profiles',
    'filters' => [],
    'searchSuggestions' => [],
])

<section class="py-8 px-4">
    <div class="max-w-7xl mx-auto space-y-4">
        @if ($errors->has('profile') || $errors->has('page'))
            <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first('profile') ?: $errors->first('page') }}
            </div>
        @endif

        {{-- Filter form: keep list focused by name/slug/description search. --}}
        <form method="GET" class="rounded-lg bg-white p-4 shadow">
            <div class="flex flex-col gap-3 md:flex-row md:items-end">
                <x-search-bar
                    class="flex-1"
                    :value="$filters['search'] ?? ''"
                    :label="__('Search')"
                    :placeholder="__('Profile name, slug, description...')"
                    :suggestions="$searchSuggestions"
                />
            </div>
        </form>

        @can('create_profiles')
            <x-services.create-action
                href="{{ route('profile.create') }}"
                label="{{ __('Create Profile') }}"
                class="w-full justify-center"
            />
        @endcan

        {{-- Main data table with row-level actions. --}}
        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Slug') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Pages') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Screens') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Updated') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($profiles as $profile)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-900">{{ $profile->name }}</p>
                                    <p class="text-xs text-gray-500">{{ __('By') }} {{ $profile->createdBy?->name ?? __('System') }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $profile->slug }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $profile->pages_count }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $profile->screens_count }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $profile->updated_at?->diffForHumans() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <x-secondary-link href="{{ route('profile.show', ['profile' => $profile]) }}">
                                            {{ __('View') }}
                                        </x-secondary-link>

                                        @can('update', $profile)
                                            <x-primary-link href="{{ route('profile.edit', ['profile' => $profile]) }}">
                                                {{ __('Edit') }}
                                            </x-primary-link>
                                        @endcan

                                        @can('delete', $profile)
                                            <form
                                                method="POST"
                                                action="{{ route('profile.destroy', ['profile' => $profile]) }}"
                                                data-confirm-title="{{ __('Delete profile') }}"
                                                data-confirm-message="{{ __('Delete the profile :name?', ['name' => $profile->name]) }}"
                                                data-confirm-label="{{ __('Delete') }}"
                                                data-cancel-label="{{ __('Cancel') }}"
                                                data-confirm-variant="danger"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <x-danger-button>{{ __('Delete') }}</x-danger-button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                                    {{ __('No profiles found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $profiles->links() }}
        </div>
    </div>
</section>
