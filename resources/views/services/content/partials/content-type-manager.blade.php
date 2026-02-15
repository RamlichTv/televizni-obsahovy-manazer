<div class="flex flex-col lg:flex-row gap-6">
    {{-- Seznam typů --}}
    <div class="flex-1 overflow-x-auto">
        <h3 class="text-lg font-bold text-gray-700 mb-3">{{ __('Existing Types') }}</h3>
        <table class="min-w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Extensions</th>
                    <th class="px-4 py-2">Max Size</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contentTypes as $type)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium text-gray-900">{{ $type->name }}</td>
                        <td class="px-4 py-2">
                            {{ implode(', ', $type->extension_whitelist ?? []) }}
                        </td>
                        <td class="px-4 py-2">
                            {{ round($type->max_size / 1024 / 1024) }} MB
                        </td>
                        <td class="px-4 py-2">
                            <form action="{{ route('content.types.destroy', $type) }}" method="POST" onsubmit="return confirm('Delete Type? Content associated with this type might break.');">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-500 hover:underline">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-2 text-center">No types defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

{{-- Formulář pro přidání --}}
    <div class="w-full lg:w-1/3 lg:border-l lg:pl-6 border-gray-200">
        <h3 class="text-lg font-bold text-gray-700 mb-3">{{ __('Add Content Type') }}</h3>
        
        {{-- Alpine.js state: isFile --}}
        <form action="{{ route('content.types.store') }}" method="POST" class="space-y-3" x-data="{ isFile: false }">
            @csrf
            
            {{-- Name (Vždy viditelné) --}}
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Name</label>
                <input type="text" name="name" placeholder="e.g. Animation" 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]" required>
            </div>

            {{-- Checkbox Is File --}}
            <div class="flex items-center gap-2 py-2">
                <input type="checkbox" id="is_file" name="is_file" value="1" x-model="isFile"
                       class="rounded border-gray-300 text-[#077CB6] shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]">
                <label for="is_file" class="text-sm font-medium text-gray-700 cursor-pointer select-none">
                    {{ __('Is this a file?') }}
                </label>
            </div>

            {{-- File fields (Zobrazí se jen když isFile == true) --}}
            <div x-show="isFile" x-transition class="space-y-3">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Extensions (comma separated)</label>
                    {{-- Pozor: Přejmenováno na 'extensions' aby to sedělo s Controllerem --}}
                    <input type="text" name="extensions" placeholder="json, lottie, gif" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                           :required="isFile"> 
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Max Size (MB)</label>
                    <input type="number" name="max_size_mb" placeholder="10" min="1" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                           :required="isFile">
                </div>
            </div>

            <x-input-error class="mt-2" :messages="$errors->get('error')" />

            <x-primary-button class="w-full justify-center">
                {{ __('Create Type') }}
            </x-primary-button>
        </form>
    </div>
</div>