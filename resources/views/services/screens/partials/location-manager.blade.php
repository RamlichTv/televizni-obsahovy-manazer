{{--
    This partial manages the building/floor/room hierarchy used by screen placement.
    Each panel supports inline editing and creation so operators can maintain location data in one place.
--}}
<div x-data="{ editBuilding: null, editFloor: null, editRoom: null }" class="space-y-4">
    {{-- Surface the first validation error at the top of the manager. --}}
    @if ($errors->any())
        <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Three-column layout: buildings, floors, and rooms. --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        
        {{-- Buildings panel with inline edit/delete and a dedicated create form. --}}
        <section class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <h4 class="text-sm font-semibold text-gray-900">{{ __('Buildings') }}</h4>
            <p class="mt-1 text-xs text-gray-500">{{ __('Each building can contain multiple floors and rooms.') }}</p>

            <div class="mt-3 space-y-2 max-h-80 overflow-auto pr-1">
                @forelse ($buildings as $building)
                    <article class="rounded-lg border border-gray-200 bg-white p-3">
                        {{-- Display mode for an existing building row. --}}
                        <template x-if="editBuilding !== {{ $building->id }}">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $building->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $building->address ?: __('No address') }}</p>
                                        <p class="mt-1 text-[11px] text-gray-500">
                                            {{ __('Floors') }}: {{ $building->floors_count }} |
                                            {{ __('Rooms') }}: {{ $building->rooms_count }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="text-xs text-blue-600 hover:underline" @click="editBuilding = {{ $building->id }}">
                                            {{ __('Edit') }}
                                        </button>
                                        <form method="POST" action="{{ route('building.destroy', ['building' => $building]) }}" onsubmit="return confirm('{{ __('Delete this building?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:underline">{{ __('Delete') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Inline edit mode for this building row. --}}
                        <template x-if="editBuilding === {{ $building->id }}">
                            <form method="POST" action="{{ route('building.update', ['building' => $building]) }}" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <x-text-input name="building_name" type="text" class="block w-full text-sm" :value="$building->name" required />
                                <x-text-input name="address" type="text" class="block w-full text-sm" :value="$building->address" placeholder="{{ __('Address (optional)') }}" />
                                <div class="flex items-center gap-2">
                                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                                    <button type="button" class="text-xs text-gray-600 hover:underline" @click="editBuilding = null">{{ __('Cancel') }}</button>
                                </div>
                            </form>
                        </template>
                    </article>
                @empty
                    <p class="text-xs text-gray-500 italic">{{ __('No buildings yet.') }}</p>
                @endforelse
            </div>

            {{-- Create form for a new building entry. --}}
            <form method="POST" action="{{ route('building.store') }}" class="mt-4 space-y-2 border-t border-gray-200 pt-4">
                @csrf
                <x-input-label for="new_building_name" :value="__('Add Building')" />
                <x-text-input id="new_building_name" name="building_name" type="text" class="block w-full text-sm" :value="old('building_name')" required />
                <x-text-input name="address" type="text" class="block w-full text-sm" :value="old('address')" placeholder="{{ __('Address (optional)') }}" />
                <x-primary-button class="w-full justify-center">{{ __('Create Building') }}</x-primary-button>
            </form>
        </section>

        {{-- Floors panel linked to buildings, including reassignment on edit. --}}
        <section class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <h4 class="text-sm font-semibold text-gray-900">{{ __('Floors') }}</h4>
            <p class="mt-1 text-xs text-gray-500">{{ __('Floors belong to exactly one building.') }}</p>

            <div class="mt-3 space-y-2 max-h-80 overflow-auto pr-1">
                @forelse ($floors as $floor)
                    <article class="rounded-lg border border-gray-200 bg-white p-3">
                        {{-- Display mode for an existing floor row. --}}
                        <template x-if="editFloor !== {{ $floor->id }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $floor->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $floor->building?->name ?? __('No building') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="text-xs text-blue-600 hover:underline" @click="editFloor = {{ $floor->id }}">
                                        {{ __('Edit') }}
                                    </button>
                                    <form method="POST" action="{{ route('floor.destroy', ['floor' => $floor]) }}" onsubmit="return confirm('{{ __('Delete this floor?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </div>
                        </template>

                        {{-- Inline edit mode for this floor row. --}}
                        <template x-if="editFloor === {{ $floor->id }}">
                            <form method="POST" action="{{ route('floor.update', ['floor' => $floor]) }}" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <x-select name="building_id" id="floor_building_{{ $floor->id }}">
                                    @foreach ($buildings as $building)
                                        <option value="{{ $building->id }}" @selected((int) $floor->building_id === (int) $building->id)>
                                            {{ $building->name }}
                                        </option>
                                    @endforeach
                                </x-select>
                                <x-text-input name="floor_name" type="text" class="block w-full text-sm" :value="$floor->name" required />
                                <div class="flex items-center gap-2">
                                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                                    <button type="button" class="text-xs text-gray-600 hover:underline" @click="editFloor = null">{{ __('Cancel') }}</button>
                                </div>
                            </form>
                        </template>
                    </article>
                @empty
                    <p class="text-xs text-gray-500 italic">{{ __('No floors yet.') }}</p>
                @endforelse
            </div>

            {{-- Create form for a new floor entry. --}}
            <form method="POST" action="{{ route('floor.store') }}" class="mt-4 space-y-2 border-t border-gray-200 pt-4">
                @csrf
                <x-input-label for="new_floor_name" :value="__('Add Floor')" />
                <x-select name="building_id" id="new_floor_building" required>
                    <option value="">{{ __('Select building') }}</option>
                    @foreach ($buildings as $building)
                        <option value="{{ $building->id }}" @selected((string) old('building_id') === (string) $building->id)>
                            {{ $building->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-text-input id="new_floor_name" name="floor_name" type="text" class="block w-full text-sm" :value="old('floor_name')" required />
                <x-primary-button class="w-full justify-center">{{ __('Create Floor') }}</x-primary-button>
            </form>
        </section>

        {{-- Rooms panel allows optional building/floor assignment and inline maintenance. --}}
        <section class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <h4 class="text-sm font-semibold text-gray-900">{{ __('Rooms') }}</h4>
            <p class="mt-1 text-xs text-gray-500">{{ __('Rooms can be assigned to building and floor.') }}</p>

            <div class="mt-3 space-y-2 max-h-80 overflow-auto pr-1">
                @forelse ($rooms as $room)
                    <article class="rounded-lg border border-gray-200 bg-white p-3">
                        {{-- Display mode for an existing room row. --}}
                        <template x-if="editRoom !== {{ $room->id }}">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $room->name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $room->building?->name ?? __('No building') }}
                                        @if ($room->floor?->name)
                                            • {{ __('Floor') }} {{ $room->floor->name }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="text-xs text-blue-600 hover:underline" @click="editRoom = {{ $room->id }}">
                                        {{ __('Edit') }}
                                    </button>
                                    <form method="POST" action="{{ route('room.destroy', ['room' => $room]) }}" onsubmit="return confirm('{{ __('Delete this room?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </div>
                        </template>

                        {{-- Inline edit mode for this room row. --}}
                        <template x-if="editRoom === {{ $room->id }}">
                            <form method="POST" action="{{ route('room.update', ['room' => $room]) }}" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <x-select name="building_id" id="room_building_{{ $room->id }}">
                                    <option value="">{{ __('No building') }}</option>
                                    @foreach ($buildings as $building)
                                        <option value="{{ $building->id }}" @selected((int) ($room->building_id ?? 0) === (int) $building->id)>
                                            {{ $building->name }}
                                        </option>
                                    @endforeach
                                </x-select>
                                <x-select name="floor_id" id="room_floor_{{ $room->id }}">
                                    <option value="">{{ __('No floor') }}</option>
                                    @foreach ($floors as $floor)
                                        <option value="{{ $floor->id }}" @selected((int) ($room->floor_id ?? 0) === (int) $floor->id)>
                                            {{ $floor->building?->name ? $floor->building->name . ' / ' : '' }}{{ $floor->name }}
                                        </option>
                                    @endforeach
                                </x-select>
                                <x-text-input name="room_name" type="text" class="block w-full text-sm" :value="$room->name" required />
                                <div class="flex items-center gap-2">
                                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                                    <button type="button" class="text-xs text-gray-600 hover:underline" @click="editRoom = null">{{ __('Cancel') }}</button>
                                </div>
                            </form>
                        </template>
                    </article>
                @empty
                    <p class="text-xs text-gray-500 italic">{{ __('No rooms yet.') }}</p>
                @endforelse
            </div>

            {{-- Create form for a new room entry. --}}
            <form method="POST" action="{{ route('room.store') }}" class="mt-4 space-y-2 border-t border-gray-200 pt-4">
                @csrf
                <x-input-label for="new_room_name" :value="__('Add Room')" />
                <x-select name="building_id" id="new_room_building">
                    <option value="">{{ __('No building') }}</option>
                    @foreach ($buildings as $building)
                        <option value="{{ $building->id }}" @selected((string) old('building_id') === (string) $building->id)>
                            {{ $building->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-select name="floor_id" id="new_room_floor">
                    <option value="">{{ __('No floor') }}</option>
                    @foreach ($floors as $floor)
                        <option value="{{ $floor->id }}" @selected((string) old('floor_id') === (string) $floor->id)>
                            {{ $floor->building?->name ? $floor->building->name . ' / ' : '' }}{{ $floor->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-text-input id="new_room_name" name="room_name" type="text" class="block w-full text-sm" :value="old('room_name')" required />
                <x-primary-button class="w-full justify-center">{{ __('Create Room') }}</x-primary-button>
            </form>
        </section>
    </div>
</div>
