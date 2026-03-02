@props([
    'value',
    'required' => false
])

@php
    $required = $required ? "after:content-['*'] after:text-orange-700" : '';
    $class = 'block font-medium text-sm text-gray-700 ' . $required;
@endphp

<label {{ $attributes->merge(['class' =>  $class]) }}>
    {{ $value ?? $slot }}
</label>
