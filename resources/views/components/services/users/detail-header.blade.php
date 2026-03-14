{{--
    Overview:
    This view composes the components > services > users > detail-header interface, rendering navigation and privileged actions for a user profile.
--}}

@props([
    'user',
])

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 w-full">
    {{-- Left side: navigation context. --}}
    <div class="flex items-center gap-4">
        <x-secondary-link href="{{ route('users.index') }}">
            <x-icon name="arrow" class="size-4 mr-2 rotate-180" />
            {{ __('Back to Users') }}
        </x-secondary-link>
        <h1 class="text-2xl font-bold text-gray-800">{{ __('User Detail') }}</h1>
    </div>

    {{-- Right side: management actions guarded by policy checks. --}}
    <div class="flex items-center gap-2">
        @can('manage_users')
            <x-secondary-link href="{{ route('users.edit', ['user' => $user]) }}">
                <x-icon name="pen" class="size-4 mr-2" />
                {{ __('Edit') }}
            </x-secondary-link>
        @endcan

        @can('manage_users')
            @if (!auth()->user()->is($user))
                <form
                    method="POST"
                    action="{{ route('users.destroy', ['user' => $user]) }}"
                    data-confirm-title="{{ __('Delete user') }}"
                    data-confirm-message="{{ __('Delete the user :name?', ['name' => $user->name ?: $user->username]) }}"
                    data-confirm-label="{{ __('Delete') }}"
                    data-cancel-label="{{ __('Cancel') }}"
                    data-confirm-variant="danger"
                >
                    @csrf
                    @method('DELETE')
                    <x-danger-button>
                        <x-icon name="trash" class="size-4 mr-2" />
                        {{ __('Delete') }}
                    </x-danger-button>
                </form>
            @endif
        @endcan
    </div>
</div>
