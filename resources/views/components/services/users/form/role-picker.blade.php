{{--
    Overview:
    This view composes the components > services > users > form > role-picker interface, wrapping role assignment with validation feedback.
--}}

@props([
    'rolesForPicker' => collect(),
    'selectedRoleIds' => collect(),
])

<div>
    {{-- Shared multi-select picker renders role chips with color dots and ACL badge where applicable. --}}
    <x-multi-select-picker
        :label="__('Roles')"
        input-name="roles[]"
        :items="$rolesForPicker"
        :selected="$selectedRoleIds"
        :placeholder="__('Role')"
        :no-results-text="__('No assignable roles available.')"
        :add-aria-label="__('Add role')"
        :remove-aria-label="__('Remove role')"
        :show-color-dot="true"
        :show-badge="true"
        class="flex-1"
    />

    <x-input-error class="mt-2" :messages="$errors->get('roles')" />
    <x-input-error class="mt-2" :messages="$errors->get('roles.*')" />
</div>
