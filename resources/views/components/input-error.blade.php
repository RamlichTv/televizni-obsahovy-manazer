@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        @foreach ($messages as $message)
            @if (is_iterable($message))
                @foreach ($message as $nested)
                    <li>{{ $nested }}</li>
                @endforeach
            @else
                <li>{{ $message }}</li>
            @endif
        @endforeach
    </ul>
@endif
