{{--
    Overview:
    This view composes the components > services > users > table-row interface, rendering one user listing row with policy-aware role visibility and actions.
--}}

@props([
    'managedUser',
])

<tr class="hover:bg-gray-50 transition">
    {{-- Avatar column. --}}
    <td class="px-4 py-3">
        <div class="flex items-center">
            <img
                src="{{ $managedUser->avatarUrl() }}"
                alt="{{ $managedUser->name ?: $managedUser->username }}"
                class="h-10 w-10 rounded-full object-cover border border-gray-200"
            >
        </div>
    </td>

    {{-- Identity column (display name + canonical username/email line). --}}
    <td class="px-4 py-3">
        <div class="text-sm font-semibold text-gray-900">{{ $managedUser->name ?: $managedUser->username }}</div>
        <div class="text-xs text-gray-500">{{ $managedUser->username }} • {{ $managedUser->email }}</div>
    </td>

    <td class="px-4 py-3 text-sm text-gray-700">
        @php
            // Keep role visibility consistent with other user-facing screens.
            $visibleRoles = Auth::user()->isAdmin()
                ? $managedUser->roles
                : $managedUser->roles
                    ->where('is_hidden', false)
                    ->where('name', '!=', 'Administrator');
        @endphp

        @if ($visibleRoles->isEmpty())
            <span class="text-gray-400">{{ __('None') }}</span>
        @else
            <div class="flex flex-wrap gap-1.5">
                @foreach ($visibleRoles as $role)
                    <x-services.roles.role :role="$role" />
                @endforeach
            </div>
        @endif
    </td>

    <td class="px-4 py-3 text-sm text-gray-700">
        {{ $managedUser->permissions->count() }}
    </td>

    <td class="px-4 py-3 text-sm text-gray-700">
        {{ $managedUser->created_at?->format('Y-m-d') }}
    </td>

    {{-- Action set remains policy-gated and prevents self-delete. --}}
    <td class="px-4 py-3">
        <div class="flex justify-end gap-2">
            <x-secondary-link href="{{ route('users.show', ['user' => $managedUser]) }}">
                {{ __('View') }}
            </x-secondary-link>

            @can('manage_users')
                <x-secondary-link href="{{ route('users.edit', ['user' => $managedUser]) }}">
                    {{ __('Edit') }}
                </x-secondary-link>
            @endcan

            @can('manage_users')
                @if (!auth()->user()->is($managedUser))
                    <form
                        method="POST"
                        action="{{ route('users.destroy', ['user' => $managedUser]) }}"
                        data-confirm-title="{{ __('Delete user') }}"
                        data-confirm-message="{{ __('Delete the user :name?', ['name' => $managedUser->name ?: $managedUser->username]) }}"
                        data-confirm-label="{{ __('Delete') }}"
                        data-cancel-label="{{ __('Cancel') }}"
                        data-confirm-variant="danger"
                    >
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Delete') }}</x-danger-button>
                    </form>
                @endif
            @endcan
        </div>
    </td>
</tr>
