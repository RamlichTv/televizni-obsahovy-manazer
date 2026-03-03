{{--
    Overview:
    This view composes the services > screens > partials > form interface, arranging reusable components and route-driven actions so the page stays readable while presentation concerns remain separated from backend logic.
--}}

@php
    /** @var \App\Models\Screen|null $screen */
    $isEdit = isset($screen) && $screen !== null;

    // Preserve whitelist/blacklist textarea content after validation failures.
    $whitelistSource = old('ip_whitelist');
    if (!is_array($whitelistSource)) {
        $whitelistSource = $isEdit ? (array) ($screen->ip_whitelist ?? []) : [];
    }
    $whitelistText = old('ip_whitelist_text', collect($whitelistSource)->filter()->implode(PHP_EOL));

    $blacklistSource = old('ip_blacklist');
    if (!is_array($blacklistSource)) {
        $blacklistSource = $isEdit ? (array) ($screen->ip_blacklist ?? []) : [];
    }
    $blacklistText = old('ip_blacklist_text', collect($blacklistSource)->filter()->implode(PHP_EOL));

    $defaultOnlineStatus = $statuses->first(fn ($status): bool => strtolower((string) $status->name) === 'online')
        ?? $statuses->first();
    $defaultStatusId = $defaultOnlineStatus?->id ? (string) $defaultOnlineStatus->id : '';
    $selectedStatusId = (string) old(
        'status_id',
        $isEdit ? (string) ($screen->status_id ?? $defaultStatusId) : $defaultStatusId,
    );
    $selectedStatus = $statuses->first(fn ($status): bool => (string) $status->id === $selectedStatusId) ?? $defaultOnlineStatus;
    $statusName = strtolower((string) ($selectedStatus->name ?? 'online'));

    $statusPillClasses = match ($statusName) {
        'online' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'maintenance' => 'border-amber-200 bg-amber-50 text-amber-700',
        'offline' => 'border-red-200 bg-red-50 text-red-700',
        default => 'border-slate-200 bg-slate-50 text-slate-700',
    };
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    @if (!$isEdit && $defaultStatusId !== '')
        {{-- Create flow always starts online; hide status input in create UI. --}}
        <input type="hidden" name="status_id" value="{{ old('status_id', $defaultStatusId) }}">
    @endif

    <div class="relative mx-auto w-full max-w-384 overflow-hidden rounded-4xl bg-[#343434] p-3 shadow-xl sm:p-4">
        <div class="pointer-events-none absolute -right-24 -top-20 h-52 w-52 rounded-full bg-[#444444]/20 blur-2xl"></div>

        <div class="relative rounded-[1.75rem] border-8 border-[#444444] bg-[#ffffff] p-3 sm:p-4">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ __('Screen Console') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Configure display hardware, assignment, and access policies.') }}</p>
                </div>

                @if ($isEdit)
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusPillClasses }}">
                        <span class="inline-block h-2 w-2 rounded-full {{ $statusName === 'online' ? 'bg-emerald-500' : ($statusName === 'offline' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                        {{ $selectedStatus?->name ? ucfirst((string) $selectedStatus->name) : __('Online') }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="md:col-span-2 rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <x-input-label for="name" :value="__('Screen Name')" :required="true" />
                    <x-text-input
                        id="name"
                        name="name"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('name', $isEdit ? $screen->name : '')"
                        required
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div class="rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <x-input-label for="hardware_id" :value="__('Hardware ID')" />
                    <x-text-input
                        id="hardware_id"
                        name="hardware_id"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('hardware_id', $isEdit ? $screen->hardware_id : '')"
                        placeholder="{{ __('Optional unique device identifier') }}"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('hardware_id')" />
                </div>

                @if ($isEdit)
                    <div class="rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                        <x-input-label for="status_id" :value="__('Status')" :required="true" />
                        <x-select id="status_id" name="status_id" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" @selected((string) old('status_id', (string) ($screen->status_id ?? $defaultStatusId)) === (string) $status->id)>
                                    {{ ucfirst((string) $status->name) }}
                                </option>
                            @endforeach
                        </x-select>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Offline and maintenance statuses show dedicated monitor pages.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('status_id')" />
                    </div>
                @endif

                <div class="rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <x-input-label for="profile_id" :value="__('Assigned Profile')" />
                    <x-select id="profile_id" name="profile_id">
                        <option value="">{{ __('No profile assigned') }}</option>
                        @foreach ($profiles as $profile)
                            <option value="{{ $profile->id }}" @selected((string) old('profile_id', $isEdit ? $screen->profile_id : '') === (string) $profile->id)>
                                {{ $profile->name }} ({{ $profile->slug }})
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error class="mt-2" :messages="$errors->get('profile_id')" />
                </div>

                <div class="rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <x-input-label for="room_id" :value="__('Room')" />
                    <x-select id="room_id" name="room_id">
                        <option value="">{{ __('No room assigned') }}</option>
                        @foreach ($rooms as $room)
                            @php
                                $roomLabel = $room->name;
                                if ($room->building?->name) {
                                    $roomLabel = $room->building->name . ' / ' . $roomLabel;
                                }
                                if ($room->floor?->name) {
                                    $roomLabel .= ' (' . __('Floor') . ' ' . $room->floor->name . ')';
                                }
                            @endphp
                            <option value="{{ $room->id }}" @selected((string) old('room_id', $isEdit ? $screen->room_id : '') === (string) $room->id)>
                                {{ $roomLabel }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error class="mt-2" :messages="$errors->get('room_id')" />
                </div>

                <div class="rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <x-input-label for="orientation_id" :value="__('Orientation')" />
                    <x-select id="orientation_id" name="orientation_id">
                        <option value="">{{ __('No orientation') }}</option>
                        @foreach ($orientations as $orientation)
                            <option value="{{ $orientation->id }}" @selected((string) old('orientation_id', $isEdit ? $screen->orientation_id : '') === (string) $orientation->id)>
                                {{ ucfirst((string) $orientation->name) }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error class="mt-2" :messages="$errors->get('orientation_id')" />
                </div>

                <div class="md:col-span-2 rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Pixel Size') }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="width" :value="__('Width (px)')" />
                            <x-text-input
                                id="width"
                                name="width"
                                type="number"
                                min="100"
                                max="16384"
                                class="mt-1 block w-full"
                                :value="old('width', $isEdit ? $screen->width : '')"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('width')" />
                        </div>

                        <div>
                            <x-input-label for="height" :value="__('Height (px)')" />
                            <x-text-input
                                id="height"
                                name="height"
                                type="number"
                                min="100"
                                max="16384"
                                class="mt-1 block w-full"
                                :value="old('height', $isEdit ? $screen->height : '')"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('height')" />
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ __('Used as the playback canvas size in monitor mode.') }}</p>
                </div>

                <div class="rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <x-input-label for="auto_refresh_interval" :value="__('Auto Refresh (seconds)')" />
                    <x-text-input
                        id="auto_refresh_interval"
                        name="auto_refresh_interval"
                        type="number"
                        min="5"
                        max="86400"
                        class="mt-1 block w-full"
                        :value="old('auto_refresh_interval', $isEdit ? $screen->auto_refresh_interval : '')"
                        placeholder="{{ __('Leave empty to disable auto refresh') }}"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('auto_refresh_interval')" />
                </div>

                <div class="rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <label for="is_interactive" class="cursor-pointer flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ __('Interactive Screen') }}</p>
                            <p class="text-xs text-slate-500">{{ __('Enable touch/interactivity mode for this device.') }}</p>
                        </div>
                        <div class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="is_interactive" value="0">
                            <input
                                id="is_interactive"
                                name="is_interactive"
                                type="checkbox"
                                value="1"
                                class="peer sr-only"
                                @checked((bool) old('is_interactive', $isEdit ? $screen->is_interactive : false))
                            >
                            <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-[#444444] after:absolute after:left-1 after:top-1 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                        </div>
                    </label>
                    <x-input-error class="mt-2" :messages="$errors->get('is_interactive')" />
                </div>

                <div class="md:col-span-2 rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <x-input-label for="description" :value="__('Description')" />
                    <x-textarea id="description" name="description" rows="3" class="mt-1 block w-full">{{ old('description', $isEdit ? $screen->description : '') }}</x-textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                </div>

                <div class="md:col-span-2 rounded-xl border border-[#444444] bg-[#ffffff] p-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Network Access') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Whitelist restricts access. Blacklist always denies.') }}</p>

                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <x-input-label for="ip_whitelist_text" :value="__('IP Whitelist')" />
                            <x-textarea
                                id="ip_whitelist_text"
                                name="ip_whitelist_text"
                                rows="4"
                                class="mt-1 block w-full font-mono text-sm"
                                placeholder="{{ __('One IP per line. If set, only these IPs can open the screen.') }}"
                            >{{ $whitelistText }}</x-textarea>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Example: 192.168.1.15') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('ip_whitelist')" />
                            <x-input-error class="mt-2" :messages="$errors->get('ip_whitelist.*')" />
                        </div>

                        <div>
                            <x-input-label for="ip_blacklist_text" :value="__('IP Blacklist')" />
                            <x-textarea
                                id="ip_blacklist_text"
                                name="ip_blacklist_text"
                                rows="4"
                                class="mt-1 block w-full font-mono text-sm"
                                placeholder="{{ __('One IP per line. These IPs are always blocked.') }}"
                            >{{ $blacklistText }}</x-textarea>
                            <p class="mt-1 text-xs text-slate-500">{{ __('Blacklist has priority over whitelist.') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('ip_blacklist')" />
                            <x-input-error class="mt-2" :messages="$errors->get('ip_blacklist.*')" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto mt-2 h-1.5 w-24 rounded-full bg-[#444444]"></div>
        <div class="mx-auto mt-0.5 h-2.5 w-36 rounded-full bg-[#343434]"></div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4">
        <x-secondary-link href="{{ route('screens.index') }}">{{ __('Cancel') }}</x-secondary-link>
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
    </div>
</form>
