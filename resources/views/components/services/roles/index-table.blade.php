@props(['roles'])

{{--
    Roles service component:
    Data table for role listing with counts, visibility state, and row actions.
--}}

<div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            {{-- Table header --}}
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Role') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Permissions') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Users') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Visibility') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Actions') }}</th>
                </tr>
            </thead>

            {{-- Table body --}}
            <tbody class="divide-y divide-gray-100">
                @forelse ($roles as $role)
                    <tr class="hover:bg-gray-50 transition">

                        {{-- Role identity with color marker --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full border border-black/10" style="background-color: {{ $role->color }}"></span>
                                <span class="text-sm font-semibold text-gray-900">{{ $role->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $role->permissions_count }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $role->users_count }}
                        </td>
                        
                        {{-- Visibility badge --}}
                        <td class="px-4 py-3 text-sm text-gray-700">
                            @if ($role->is_hidden)
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                    {{ __('Hidden') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                    {{ __('Visible') }}
                                </span>
                            @endif
                        </td>

                        {{-- Edit/delete controls --}}
                        <td class="px-4 py-3">
                            <x-services.roles.row-actions :role="$role" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                            {{ __('No roles found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
