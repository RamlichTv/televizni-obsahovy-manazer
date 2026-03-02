{{--
    Overview:
    This view composes the components > services > users > summary-card interface, rendering avatar and primary identity metadata for a user.
--}}

@props([
    'user',
])

<div class="bg-white shadow-sm sm:rounded-lg p-6">
    <div class="flex flex-col md:flex-row md:items-start gap-6">
        {{-- Avatar block mirrors the currently stored user image. --}}
        <div>
            <img
                src="{{ $user->avatarUrl() }}"
                alt="{{ $user->name ?? $user->username }}"
                class="h-20 w-20 rounded-full object-cover border border-gray-300"
            >
        </div>

        {{-- Core profile fields shown as read-only metadata in two-column layout. --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-1">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Username') }}</p>
                <p class="mt-1 text-sm text-gray-900">{{ $user->username }}</p>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Name') }}</p>
                <p class="mt-1 text-sm text-gray-900">{{ $user->name ?: '—' }}</p>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Email') }}</p>
                <p class="mt-1 text-sm text-gray-900">{{ $user->email }}</p>
            </div>

            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('Created') }}</p>
                <p class="mt-1 text-sm text-gray-900">{{ $user->created_at?->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </div>
</div>
