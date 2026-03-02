{{--
    Overview:
    This view composes the components > services > users > form > basic-fields interface, rendering identity and credential fields for create/edit flows.
--}}

@props([
    'isEdit' => false,
    'user' => null,
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Username is immutable in edit mode; create mode requires username + confirmation. --}}
    @if ($isEdit)
        <div>
            <x-input-label :value="__('Username')" />
            <div class="mt-1 flex items-justify border border-gray-300 rounded-md shadow-sm px-3 py-2 text-sm text-gray-700 bg-gray-50">
                {{ $user->username }}
            </div>
        </div>
    @else
        <div>
            <x-input-label for="username" :value="__('Username')" :required="true" />
            <x-text-input
                id="username"
                name="username"
                type="text"
                class="mt-1 block w-full"
                :value="old('username')"
                required
                autocomplete="new-username"
            />
            <x-input-error class="mt-2" :messages="$errors->get('username')" />
        </div>

        <div>
            <x-input-label for="username_confirmation" :value="__('Confirm Username')" :required="true" />
            <x-text-input
                id="username_confirmation"
                name="username_confirmation"
                type="text"
                class="mt-1 block w-full"
                required
                autocomplete="new-username"
            />
            <x-input-error class="mt-2" :messages="$errors->get('username_confirmation')" />
        </div>
    @endif

    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $isEdit ? $user->name : '')"
        />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="email" :value="__('Email')" :required="true" />
        <x-text-input
            id="email"
            name="email"
            type="email"
            class="mt-1 block w-full"
            :value="old('email', $isEdit ? $user->email : '')"
            required
            autocomplete="email"
        />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>

    <div>
        <x-input-label for="password" :value="$isEdit ? __('New Password') : __('Password')" :required="!$isEdit" />
        <x-text-input
            id="password"
            name="password"
            type="password"
            class="mt-1 block w-full"
            autocomplete="new-password"
            :required="!$isEdit"
        />
        {{-- Edit mode supports optional password rotation without forcing reset. --}}
        @if ($isEdit)
            <p class="mt-1 text-xs text-gray-500">{{ __('Leave empty to keep current password.') }}</p>
        @endif
        <x-input-error class="mt-2" :messages="$errors->get('password')" />
    </div>

    <div>
        <x-input-label for="password_confirmation" :value="$isEdit ? __('Confirm New Password') : __('Confirm Password')" :required="!$isEdit" />
        <x-text-input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            class="mt-1 block w-full"
            autocomplete="new-password"
            :required="!$isEdit"
        />
        <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
    </div>
</div>
