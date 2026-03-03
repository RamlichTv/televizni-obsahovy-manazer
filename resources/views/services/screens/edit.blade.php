<x-app-layout title="{{ $screen->name }}">
    <x-slot name="screensNavTitle">
        {{ __('Edit') }}
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between w-full">
            <div class="flex items-center gap-3">
                <x-secondary-link href="{{ route('screens.index') }}">
                    <x-icon name="arrow" class="w-4 h-4 mr-2 rotate-180" />
                    {{ __('Back') }}
                </x-secondary-link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $screen->name }}</h1>
                    <p class="text-xs text-gray-500">{{ $screen->hardware_id ?: __('No hardware ID') }}</p>
                </div>
            </div>

            <x-secondary-link href="{{ route('screen.show', ['screen' => $screen]) }}" target="_blank" rel="noopener">
                {{ __('Open Screen URL') }}
            </x-secondary-link>
        </div>
    </x-slot>

    <section class="py-8 px-4">
        <div class="max-w-384 mx-auto space-y-4">
            @if (session('success'))
                <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Reuse the shared screen form partial for edit flow. --}}
            @include('services.screens.partials.form', [
                'action' => route('screen.update', ['screen' => $screen]),
                'method' => 'PATCH',
                'submitLabel' => __('Save Changes'),
            ])

            {{-- Hard-delete action intentionally separated from normal save actions. --}}
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-700">{{ __('Danger Zone') }}</p>
                <p class="mt-1 text-xs text-red-700">{{ __('Deleting a screen removes the TV endpoint record. This does not delete its profile.') }}</p>
                <form method="POST" action="{{ route('screen.destroy', ['screen' => $screen]) }}" class="mt-3" onsubmit="return confirm('{{ __('Delete this screen?') }}');">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>{{ __('Delete Screen') }}</x-danger-button>
                </form>
            </div>
        </div>
    </section>
</x-app-layout>
