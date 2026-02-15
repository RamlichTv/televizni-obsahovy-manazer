@props(['name', 'id', 'required' => false])

<select 
    name="{{ $name }}" 
    id="{{ $id }}"
    {{ $attributes->merge(['class' => 'mt-1 block w-full border-gray-300 focus:border-[#5EB4EE] focus:ring-[#5EB4EE] rounded-md shadow-sm']) }}
    @required($required)
    >
    {{ $slot }}
</select>