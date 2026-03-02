<div class="flex flex-col lg:flex-row gap-6" x-data="{ editingTypeId: null, editing: false }">
    {{-- Type list --}}
    <div class="flex-1 overflow-x-auto">
        <h3 class="text-lg font-bold text-gray-700 mb-3">{{ __('Existing Types') }}</h3>
        <table class="min-w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Allowed filetypes</th>
                    <th class="px-4 py-2">Max Size</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contentTypes as $type)

                    {{-- Type summary row with inline actions. --}}
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-4 py-2 font-medium text-gray-900">{{ $type->name }}</td>
                        <td class="px-4 py-2">
                            {{ implode(', ', $type->extension_whitelist ?? []) }}
                        </td>
                        <td class="px-4 py-2">
                            {{ round($type->max_size / 1024 / 1024) }} MB
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center gap-3">
                                <button type="button"
                                        class="text-blue-600 hover:underline"
                                        @click="editingTypeId = editingTypeId === {{ $type->id }} ? null : {{ $type->id }}">
                                    <span @click="editing = !editing" x-show="editingTypeId !== {{ $type->id }}">{{ __('Edit') }}</span>
                                    <span @click="editing = !editing" x-show="editingTypeId === {{ $type->id }}">{{ __('Close') }}</span>
                                </button>

                                {{-- Delete action is guarded with a confirmation prompt. --}}
                                <form action="{{ route('content.types.destroy', $type) }}" method="POST" onsubmit="return confirm('Delete Type? Content associated with this type might break.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-500 hover:underline">{{ __('Delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Expanded inline editor for the selected type row. --}}
                    <tr x-show="editingTypeId === {{ $type->id }}" x-cloak class="bg-gray-50 border-b">
                        <td colspan="4" class="px-4 py-4">
                            <form action="{{ route('content.types.update', $type) }}"
                                  method="POST"
                                  class="space-y-3"
                                  x-data="{ isFile: {{ $type->is_file ? 'true' : 'false' }} }">
                                @csrf
                                @method('PATCH')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-bold text-gray-500 uppercase">{{ __('Name') }}</label>
                                        <input type="text"
                                               name="name"
                                               value="{{ $type->name }}"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                               required>
                                    </div>

                                    <div class="flex items-center gap-2 pt-6">
                                        <input type="hidden" name="is_file" value="0">
                                        <input type="checkbox"
                                               id="is_file_{{ $type->id }}"
                                               name="is_file"
                                               value="1"
                                               x-model="isFile"
                                               class="rounded border-gray-300 text-[#077CB6] shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]">
                                        <label for="is_file_{{ $type->id }}" class="text-sm font-medium text-gray-700 cursor-pointer select-none">
                                            {{ __('Is this a file?') }}
                                        </label>
                                    </div>
                                </div>

                                <div x-show="isFile" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-bold text-gray-500 uppercase">{{ __('Extensions or MIME (comma separated)') }}</label>
                                        <input type="text"
                                               name="extensions"
                                               value="{{ implode(', ', $type->extension_whitelist ?? []) }}"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                               :required="isFile">
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-gray-500 uppercase">{{ __('Max Size (MB)') }}</label>
                                        <input type="number"
                                               name="max_size_mb"
                                               value="{{ $type->max_size ? round($type->max_size / 1024 / 1024) : '' }}"
                                               min="1"
                                               max="1024"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                               :required="isFile">
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <x-primary-button>
                                        {{ __('Update Type') }}
                                    </x-primary-button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-2 text-center">No types defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Form for adding a new type --}}
    <div x-show="!editing" class="w-full lg:w-1/3 lg:border-l lg:pl-6 border-gray-200">
        <h3 class="text-lg font-bold text-gray-700 mb-3">{{ __('Add Content Type') }}</h3>
        
        {{-- Alpine.js state: isFile --}}
        <form action="{{ route('content.types.store') }}" method="POST" class="space-y-3" x-data="{ isFile: false }">
            @csrf
            
            {{-- Name (always visible) --}}
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Name</label>
                <input type="text" name="name" placeholder="e.g. Animation" 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]" required>
            </div>

            {{-- Is file checkbox --}}
            <div class="flex items-center gap-2 py-2">
                <input type="checkbox" id="is_file" name="is_file" value="1" x-model="isFile"
                       class="rounded border-gray-300 text-[#077CB6] shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]">
                <label for="is_file" class="text-sm font-medium text-gray-700 cursor-pointer select-none">
                    {{ __('Is this a file?') }}
                </label>
            </div>

            {{-- File fields (visible only when isFile == true) --}}
            <div x-show="isFile" x-transition class="space-y-3">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">{{ __('Extensions or MIME (comma separated)') }}</label>
                    
                    {{-- Note: this uses 'extensions' to match the controller. --}}
                    <input type="text" name="extensions" placeholder="json, lottie, gif" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                           :required="isFile"> 
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Max Size (MB)</label>
                    <input type="number" name="max_size_mb" placeholder="10" min="1" max="1024"
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
