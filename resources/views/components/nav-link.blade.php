@props(['active'])

@php
$classes = ($active ?? false)
            ? 'border-[#077CB6] text-[#077CB6] hover:bg-blue-50 focus:text-[#09577B] focus:border-[#09577B] transition duration-150 ease-in-out '
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out ';
$classes = $classes . 'flex-1 inline-flex justify-center items-center px-2 pt-1 border-b-[3px] text-md font-medium leading-5 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
