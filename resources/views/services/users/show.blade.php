{{--
    User detail page.
    It keeps entity-specific errors inline while generic success banners are handled by the global toast host.
--}}

<x-app-layout title="{{ __('User Detail') }}">
    <x-slot name="usersNavTitle">
        {{ __('Detail') }}
    </x-slot>

    <x-slot name="header">
        <x-services.users.detail-header :user="$user" />
    </x-slot>

    <section class="py-8 px-4">
        <div class="max-w-5xl mx-auto space-y-6">
            @if ($errors->has('user'))
                <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first('user') }}
                </div>
            @endif

            {{-- Identity and lifecycle metadata block. --}}
            <x-services.users.summary-card :user="$user" />

            {{-- Access control summary: assigned roles and direct permissions. --}}
            <x-services.users.access-panels :user="$user" />
        </div>
    </section>
</x-app-layout>
