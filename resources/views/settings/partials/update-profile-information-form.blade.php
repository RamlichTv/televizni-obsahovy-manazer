<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form method="post" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6 sm:col-span-4">
            <x-input-label for="avatar" :value="__('Avatar')" />

            <input type="file" class="hidden"
                        id="photo"
                        name="avatar"
                        accept="image/*"
                        x-ref="photo"
                        x-on:change="
                                photoName = $refs.photo.files[0].name;
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    photoPreview = e.target.result;
                                };
                                reader.readAsDataURL($refs.photo.files[0]);
                        " />

            <div class="mt-2 flex items-center space-x-4">
                <div x-show="! photoPreview">
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name ?? $user->username }}" class="rounded-full size-50 object-cover">
                </div>

                <div x-show="photoPreview" style="display: none;">
                    <span class="block rounded-full size-50 bg-cover bg-no-repeat bg-center"
                          x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                    </span>
                </div>

                <x-secondary-button type="button" x-on:click.prevent="$refs.photo.click()">
                    {{ __('Select New Photo') }}
                </x-secondary-button>
            </div>
            
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        <div>
            <x-input-label :value="__('Username')" />
            <div class="mt-1 flex items-justify border border-gray-300 rounded-md shadow-sm">
                <x-input-label class="mx-3 my-2 text-[1rem] justify-start block w-full" :value="old('username', $user->username)"/>
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'settings-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
