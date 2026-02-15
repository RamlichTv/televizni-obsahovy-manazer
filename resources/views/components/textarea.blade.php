@props(['id', 'name', 'rows' => 3])

<textarea 
    id="{{ $id }}" 
    name="{{ $name }}" 
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => 'mt-1 block w-full border-gray-300 focus:border-[#5EB4EE] focus:ring-[#5EB4EE] rounded-md shadow-sm']) }}
>{{ $slot }}</textarea>