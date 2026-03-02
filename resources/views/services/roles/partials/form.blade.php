@php
    // Form state and defaults.
    /** @var \App\Models\Role|null $role */
    $isEdit = isset($role) && $role !== null;

    $selectedPermissionIds = collect((array) old('permissions', $isEdit ? $role->permissions->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)
        ->values();

    $selectedColor = strtoupper((string) old('color', $isEdit ? $role->color : '#D0D0D0'));

    if (!preg_match('/^#[0-9A-F]{6}$/', $selectedColor)) {
        $selectedColor = '#D0D0D0';
    }
@endphp

{{-- Role Create/Edit Form --}}
<form method="POST" action="{{ $action }}" class="space-y-8" x-data="{ roleColor: '{{ $selectedColor }}' }">
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @endif

    {{-- Basic Role Fields --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-input-label for="name" :value="__('Role Name')" :required="true"/>
            <x-text-input
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                :value="old('name', $isEdit ? $role->name : '')"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="color" :value="__('Role Color')" :required="true"/>
            <div class="mt-1 flex items-center gap-3">
                <span class="h-8 w-8 rounded-full border border-gray-300 shadow-sm" :style="`background-color: ${roleColor}`"></span>
                <input
                    id="color_picker"
                    type="color"
                    x-model="roleColor"
                    class="h-10 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1"
                    aria-label="{{ __('Choose role color') }}"
                >
                <x-text-input
                    id="color"
                    name="color"
                    type="text"
                    class="block w-full uppercase"
                    x-model="roleColor"
                    pattern="^#[0-9A-Fa-f]{6}$"
                    maxlength="7"
                    required
                />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('color')" />
        </div>
    </div>

    {{-- Visibility Controls (Admin only) --}}
    @if ($canManageHiddenRoles)
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <label for="is_hidden" class="flex items-center justify-between gap-4 cursor-pointer">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ __('Hidden Role') }}</p>
                    <p class="text-xs text-gray-500">{{ __('Hidden roles are visible only to administrators in role lists.') }}</p>
                </div>

                <div class="relative inline-flex h-6 w-11 shrink-0 items-center">
                    <input
                        id="is_hidden"
                        name="is_hidden"
                        type="checkbox"
                        value="1"
                        class="peer sr-only"
                        @checked(old('is_hidden', $isEdit ? $role->is_hidden : false))
                    >
                    <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#077CB6] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                </div>
            </label>
        </div>
    @endif

    {{-- Permission Switches --}}
    <x-services.roles.permissions-selector
        :permission-groups="$permissionGroups ?? collect()"
        :permissions="$permissions ?? []"
        :selected-permission-ids="$selectedPermissionIds"
    />

    {{-- Form Actions --}}
    <div class="flex items-center gap-4 border-t border-gray-200 pt-6">
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
        <x-secondary-link href="{{ route('roles.index') }}">
            {{ __('Cancel') }}
        </x-secondary-link>
    </div>
</form>
