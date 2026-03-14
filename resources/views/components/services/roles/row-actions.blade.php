{{--
    Roles service component:
    Action buttons for a single role row in index table.
--}}

@props(['role'])

{{-- Role row actions: edit always available, delete hidden for Administrator role. --}}
<div class="flex justify-end gap-2">
    <x-secondary-link href="{{ route('role.edit', ['role' => $role]) }}">
        {{ __('Edit') }}
    </x-secondary-link>

    {{-- Administrator role is protected from deletion. --}}
    @if ($role->name !== 'Administrator')
        <form
            method="POST"
            action="{{ route('role.destroy', ['role' => $role]) }}"
            data-confirm-title="{{ __('Delete role') }}"
            data-confirm-message="{{ __('Delete the role :name?', ['name' => $role->name]) }}"
            data-confirm-label="{{ __('Delete') }}"
            data-cancel-label="{{ __('Cancel') }}"
            data-confirm-variant="danger"
        >
            @csrf
            @method('DELETE')
            <x-danger-button>{{ __('Delete') }}</x-danger-button>
        </form>
    @endif
</div>
