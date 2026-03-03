@props([
    'href',
    'label',
    'icon' => 'plus-circle',
])

<x-primary-link
    href="{{ $href }}"
    {{ $attributes->class(['inline-flex items-center gap-2']) }}
>
    <x-icon :name="$icon" class="h-4 w-4" />
    {{ $label }}
</x-primary-link>
