<a href="{{ $link }}" {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-6 bg-white rounded-lg shadow hover:shadow-lg transition-shadow duration-200']) }}>
    @if ($icon)
    <x-icon :name="$icon" class="w-12 h-12 mb-4" />
    @endif
    @if ($title)
    <h3 class="text-lg font-semibold text-gray-900 mb-2 text-center">{{ $title }}</h3>
    @endif
    @if ($description)
    <p class="text-sm text-gray-600 text-center">{{ $description }}</p>
    @endif
</a>