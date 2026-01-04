@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[#5EB4EE] focus:ring-[#5EB4EE] rounded-md shadow-sm']) }}>
