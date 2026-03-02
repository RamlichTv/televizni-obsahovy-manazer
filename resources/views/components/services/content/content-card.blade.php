@props(['content'])

@php
    $meta = is_array($content->meta) ? $content->meta : json_decode($content->meta, true);

    $path = $meta['path'] ?? null;
    $url = $path ? asset('storage/' . $path) : null;

    $mime = $meta['mime_type'] ?? '';
    $author = $content->author;
@endphp

<div {{ $attributes->class(['group relative bg-white rounded-lg shadow hover:shadow-lg transition duration-300 overflow-hidden flex flex-col border border-gray-200 h-full']) }}>
    
    {{-- Thumbnail --}}
    <div class="relative h-40 bg-gray-100 flex items-center justify-center overflow-hidden">
        @if($url && str_contains($mime, 'image'))
            <img src="{{ $url }}" alt="{{ $content->name }}" class="w-full h-full object-cover transition transform group-hover:scale-105">
        @elseif($url && str_contains($mime, 'video'))
            <div class="relative w-full h-full flex items-center justify-center bg-black">
                <video src="{{ $url }}" class="w-full h-full object-cover opacity-60"></video>
                <svg class="w-12 h-12 text-white absolute" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path></svg>
            </div>
        @else
            {{-- Placeholder podle typu --}}
            <div class="text-gray-400 flex flex-col items-center">
                @if($content->type_id == 1) <span class="text-4xl">📷</span> {{-- Photo --}}
                @elseif($content->type_id == 2) <span class="text-4xl">🖼️</span> {{-- Poster --}}
                @elseif($content->type_id == 3) <span class="text-4xl">🎥</span> {{-- Video --}}
                @else <span class="text-4xl">📄</span> {{-- Document/Other --}}
                @endif
            </div>
        @endif

        {{-- Status Badge --}}
        <div class="absolute top-2 right-2">
            @php
                $status = App\Models\ContentStatus::find($content->status_id)->name;
            @endphp
            @if($content->status_id == 1)
                <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded">{{ __($status) }}</span>
            @elseif($content->status_id == 2)
                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">{{ __($status) }}</span>
            @elseif($content->status_id == 3)
                <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded">{{ __($status) }}</span>
            @elseif($content->status_id == 4)
                <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded">{{ __($status) }}</span>
            @endif
        </div>
    </div>

    {{-- Content Info --}}
    <div class="p-4 flex-1 flex flex-col justify-between">
        <div>
            <div class="flex justify-between items-start">
                <h3 class="text-md font-bold text-gray-900 truncate" title="{{ $content->name }}">
                    {{ $content->name }}
                </h3>
            </div>
            
            {{-- Type and Expiration --}}
            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                <span class="uppercase tracking-wide">{{ $content->type ? $content->type->name : 'Unknown' }}</span>
                @if($content->expires_at)
                    <span>• Exp: {{ \Carbon\Carbon::parse($content->expires_at)->format('d.m. y H:i') }}</span>
                @endif
            </div>

            {{-- Tags --}}
            <div class="mt-2 flex flex-wrap gap-1">
                @foreach($content->tags->take(3) as $tag)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                        #{{ $tag->name }}
                    </span>
                @endforeach

                @if($content->tags->count() > 3)
                    <span class="text-xs text-gray-500">+{{ $content->tags->count() - 3 }}</span>
                @endif
            </div>
        </div>
        
        {{-- Footer of the card (Author + Actions) --}}
        <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between items-center">
            <div class="text-xs text-gray-500 flex items-center gap-1">
                @if ($author)
                    <img src="{{ $author->avatarUrl() }}" alt="Avatar" class="w-5 h-5 rounded-full object-cover">
                @else
                    <div class="w-5 h-5 rounded-full bg-gray-300 flex items-center justify-center text-[10px] font-bold text-gray-700">
                        ?
                    </div>
                @endif

                <span class="truncate max-w-20">{{ $author->name ?? 'Unknown' }}</span>
            </div>

            @if (request()->route()->getName() !== 'content.deleted')
                <div class="flex items-center gap-2">
                    @if ($content->author_id === Auth::user()->id || Auth::user()->can('edit_content'))
                        <a href="{{ route('content.edit', $content) }}" class="relative z-10 s text-[#077CB6] hover:text-[#066a9c] hover:bg-blue-100 px-2 py-1 rounded-md text-sm font-medium">
                            {{ __('Edit') }}
                        </a>
                    @endif

                    @can('delete', $content)
                        <form action="{{ route('content.destroy', $content) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="relative z-10 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-full p-1 text-sm font-medium">
                                <x-icon name="trash" class="size-4"/>
                            </button>
                        </form>
                    @endcan
                </div>
            @endif
        </div>
    </div>
    
    <a href="{{ route('content.show', $content) }}" class="absolute inset-0 z-0"></a>
</div>