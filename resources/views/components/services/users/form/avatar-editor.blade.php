{{--
    Overview:
    This view composes the components > services > users > form > avatar-editor interface, handling client-side preview and replacement for avatar uploads.
--}}

@props([
    'user',
])

{{-- Alpine keeps only temporary preview/file-name state; persisted value is still the file input. --}}
<div x-data="{ avatarPreview: null, avatarFileName: null }" class="space-y-2">
    <x-input-label for="avatar" :value="__('Avatar')" />

    <input
        id="avatar"
        name="avatar"
        type="file"
        accept="image/*"
        class="sr-only"
        x-ref="avatarInput"
        x-on:change="
            const file = $refs.avatarInput.files[0];
            avatarFileName = file ? file.name : null;
            if (avatarPreview) URL.revokeObjectURL(avatarPreview);
            avatarPreview = file ? URL.createObjectURL(file) : null;
        "
    />

    {{-- Visual upload card with current avatar fallback when no new file is selected. --}}
    <div class="mx-auto w-full max-w-md rounded-xl border border-gray-200 bg-gray-50/70 p-6 shadow-sm">
        <div class="mx-auto h-28 w-28 overflow-hidden rounded-full border border-gray-300 bg-gray-100 ring-4 ring-white">
            <img
                x-show="avatarPreview"
                x-bind:src="avatarPreview"
                class="h-full w-full object-cover"
                alt="{{ __('Avatar preview') }}"
            >
            <img
                x-show="!avatarPreview"
                src="{{ $user->avatarUrl() }}"
                alt="{{ $user->name ?? $user->username }}"
                class="h-full w-full object-cover"
            >
        </div>

        <p class="mt-3 text-center text-xs text-gray-500">
            {{ __('PNG, JPG, WEBP or SVG up to 2MB.') }}
        </p>

        <p x-show="avatarFileName" x-text="avatarFileName" class="mt-1 text-center text-xs font-medium text-gray-700"></p>

        <div class="mt-4 flex items-center justify-center gap-2">
            <x-secondary-button type="button" x-on:click.prevent="$refs.avatarInput.click()">
                <x-icon name="upload-picture" class="mr-2 h-4 w-4" />
                {{ __('Choose Image') }}
            </x-secondary-button>

            <x-secondary-button
                x-show="avatarPreview"
                type="button"
                class="text-gray-500 hover:text-gray-700"
                x-on:click.prevent="
                    if (avatarPreview) URL.revokeObjectURL(avatarPreview);
                    avatarPreview = null;
                    avatarFileName = null;
                    $refs.avatarInput.value = '';
                "
            >
                {{ __('Clear') }}
            </x-secondary-button>
        </div>
    </div>

    <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
</div>
