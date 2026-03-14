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

    // Keep selected screensaver module stable across validation errors.
    $selectedScreensaverModuleId = trim((string) old(
        'screensaver_module_id',
        $isEdit && isset($screen) ? (string) optional($screen->screensaverModules->first())->id : '',
    ));

    $storedRefreshInterval = $isEdit ? $screen->auto_refresh_interval : null;
    $selectedRefreshUnit = old('auto_refresh_unit');
    if (!in_array($selectedRefreshUnit, ['seconds', 'minutes'], true)) {
        $selectedRefreshUnit = ($storedRefreshInterval !== null && $storedRefreshInterval % 60 === 0)
            ? 'minutes'
            : 'seconds';
    }
    $refreshInputValue = old('auto_refresh_value');
    if ($refreshInputValue === null) {
        if ($storedRefreshInterval === null) {
            $refreshInputValue = '';
        } elseif ($selectedRefreshUnit === 'minutes' && $storedRefreshInterval % 60 === 0) {
            $refreshInputValue = (string) ($storedRefreshInterval / 60);
        } else {
            $refreshInputValue = (string) $storedRefreshInterval;
        }
    }

    $defaultOnlineStatus = $statuses->first(fn ($status): bool => strtolower((string) $status->name) === 'online')
        ?? $statuses->first();
    $defaultStatusId = $defaultOnlineStatus?->id ? (string) $defaultOnlineStatus->id : '';
    $selectedStatusId = (string) old(
        'status_id',
        $isEdit ? (string) ($screen->status_id ?? $defaultStatusId) : $defaultStatusId,
    );
    $selectedStatus = $statuses->first(fn ($status): bool => (string) $status->id === $selectedStatusId) ?? $defaultOnlineStatus;
    $statusName = strtolower((string) ($selectedStatus->name ?? 'online'));

    $textInputClass = 'mt-1 block h-11 w-full text-sm';
    $selectInputClass = 'h-11 text-sm';
    $textareaInputClass = 'mt-1 block w-full text-sm';
    $nameColumnClass = $isEdit ? 'lg:col-span-8' : 'lg:col-span-12';

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

    <div class="relative mx-auto w-full max-w-7xl overflow-hidden rounded-4xl bg-[#343434] p-2.5 shadow-xl sm:p-3">
        <div class="pointer-events-none absolute inset-x-12 top-0 h-24 rounded-full bg-white/6 blur-2xl"></div>

        <div class="relative rounded-[1.35rem] border-[6px] border-[#444444] bg-white">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Screen Console') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('Configure hardware, playback, and access without the long stacked layout.') }}</p>
                </div>

                @if ($isEdit)
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusPillClasses }}">
                        <span class="inline-block h-2 w-2 rounded-full {{ $statusName === 'online' ? 'bg-emerald-500' : ($statusName === 'offline' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                        {{ $selectedStatus?->name ? ucfirst(__((string) $selectedStatus->name)) : __('Online') }}
                    </span>
                @endif
            </div>

            <div class="px-5 py-5 sm:px-6 sm:py-6">
                <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(22rem,1fr)] xl:gap-8">
                    <div class="space-y-6">
                        <section class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Screen Details') }}</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ __('Identify the display, assign it to a room, and connect a playback profile.') }}</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-12">
                                <div class="{{ $nameColumnClass }}">
                                    <x-input-label for="name" :value="__('Screen Name')" :required="true" />
                                    <x-text-input
                                        id="name"
                                        name="name"
                                        type="text"
                                        class="{{ $textInputClass }}"
                                        :value="old('name', $isEdit ? $screen->name : '')"
                                        required
                                    />
                                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                </div>

                                @if ($isEdit)
                                    <div class="lg:col-span-4">
                                        <x-input-label for="status_id" :value="__('Status')" :required="true" />
                                        <x-select id="status_id" name="status_id" class="{{ $selectInputClass }}" required>
                                            @foreach ($statuses as $status)
                                                <option value="{{ $status->id }}" @selected((string) old('status_id', (string) ($screen->status_id ?? $defaultStatusId)) === (string) $status->id)>
                                                    {{ ucfirst(__((string) $status->name)) }}
                                                </option>
                                            @endforeach
                                        </x-select>
                                        <x-input-error class="mt-2" :messages="$errors->get('status_id')" />
                                    </div>
                                @endif

                                <div class="lg:col-span-6">
                                    <x-input-label for="profile_id" :value="__('Assigned Profile')" />
                                    <x-select id="profile_id" name="profile_id" class="{{ $selectInputClass }}">
                                        <option value="">{{ __('No profile assigned') }}</option>
                                        @foreach ($profiles as $profile)
                                            <option value="{{ $profile->id }}" @selected((string) old('profile_id', $isEdit ? $screen->profile_id : '') === (string) $profile->id)>
                                                {{ $profile->name }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                    <x-input-error class="mt-2" :messages="$errors->get('profile_id')" />
                                </div>

                                <div class="lg:col-span-6">
                                    <x-input-label for="room_id" :value="__('Room')" />
                                    <x-select id="room_id" name="room_id" class="{{ $selectInputClass }}">
                                        <option value="">{{ __('No room assigned') }}</option>
                                        @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}" @selected((string) old('room_id', $isEdit ? $screen->room_id : '') === (string) $room->id)>
                                                {{ $room->breadcrumbLabel(' > ') }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                    <x-input-error class="mt-2" :messages="$errors->get('room_id')" />
                                </div>
                            </div>
                        </section>

                        <section class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Playback Setup') }}</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ __('Control screen dimensions, orientation, refresh cycle, and fallback content.') }}</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-12">
                                <div class="lg:col-span-3">
                                    <x-input-label for="orientation_id" :value="__('Orientation')" />
                                    <x-select id="orientation_id" name="orientation_id" class="{{ $selectInputClass }}">
                                        <option value="">{{ __('No orientation') }}</option>
                                        @foreach ($orientations as $orientation)
                                            <option value="{{ $orientation->id }}" @selected((string) old('orientation_id', $isEdit ? $screen->orientation_id : '') === (string) $orientation->id)>
                                                {{ ucfirst(__((string) $orientation->name)) }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('Normalizes playback to portrait or landscape.') }}</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('orientation_id')" />
                                </div>

                                <div class="lg:col-span-5">
                                    <x-input-label for="screensaver_module_id" :value="__('Screensaver Module')" />
                                    <x-select
                                        id="screensaver_module_id"
                                        name="screensaver_module_id"
                                        class="{{ $selectInputClass }}"
                                    >
                                        <option value="">{{ __('No screensaver module') }}</option>
                                        @foreach (($screensaverModules ?? []) as $screensaverModule)
                                            <option
                                                value="{{ $screensaverModule->id }}"
                                                @selected((string) $screensaverModule->id === $selectedScreensaverModuleId)
                                            >
                                                {{ $screensaverModule->name }}@if (!$screensaverModule->is_active) ({{ __('Inactive') }}) @endif
                                            </option>
                                        @endforeach
                                    </x-select>
                                    <x-input-error class="mt-2" :messages="$errors->get('screensaver_module_id')" />
                                </div>

                                <div class="lg:col-span-2">
                                    <x-input-label for="width" :value="__('Width')" />
                                    <x-text-input
                                        id="width"
                                        name="width"
                                        type="number"
                                        min="100"
                                        max="16384"
                                        class="{{ $textInputClass }}"
                                        :value="old('width', $isEdit ? $screen->width : '')"
                                    />
                                    <x-input-error class="mt-2" :messages="$errors->get('width')" />
                                </div>

                                <div class="lg:col-span-2">
                                    <x-input-label for="height" :value="__('Height')" />
                                    <x-text-input
                                        id="height"
                                        name="height"
                                        type="number"
                                        min="100"
                                        max="16384"
                                        class="{{ $textInputClass }}"
                                        :value="old('height', $isEdit ? $screen->height : '')"
                                    />
                                    <x-input-error class="mt-2" :messages="$errors->get('height')" />
                                </div>

                                <div class="lg:col-span-5">
                                    <x-input-label for="auto_refresh_value" :value="__('Auto Refresh')" />
                                    {{-- Keep the interval value and unit visually locked as one control. --}}
                                    <div class="mt-1 grid items-stretch grid-cols-[minmax(0,1fr)_6.5rem] gap-2">
                                        <x-text-input
                                            id="auto_refresh_value"
                                            name="auto_refresh_value"
                                            type="number"
                                            min="1"
                                            max="86400"
                                            class="block h-11 w-full text-sm [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                            :value="$refreshInputValue"
                                            placeholder="{{ __('Disabled') }}"
                                        />
                                        <select
                                            id="auto_refresh_unit"
                                            name="auto_refresh_unit"
                                            class="h-11 w-full rounded-md border-gray-300 bg-white px-3 text-sm shadow-sm focus:border-[#5EB4EE] focus:ring-[#5EB4EE]"
                                        >
                                            <option value="seconds" @selected($selectedRefreshUnit === 'seconds')>{{ __('s') }}</option>
                                            <option value="minutes" @selected($selectedRefreshUnit === 'minutes')>{{ __('min') }}</option>
                                        </select>
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('auto_refresh_value')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('auto_refresh_unit')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('auto_refresh_interval')" />
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6">
                        <section class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ __('Behavior') }}</h3>
                                <p class="mt-1 text-xs text-slate-500">{{ __('Control how operators interact with the screen and keep short notes nearby.') }}</p>
                            </div>

                            <div>
                                <label for="is_interactive" class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ __('Interactive Screen') }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ __('Shows manual page navigation controls on the monitor.') }}</p>
                                    </div>
                                    <div class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center">
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

                            <div>
                                <x-input-label for="description" :value="__('Description')" />
                                <x-textarea id="description" name="description" rows="5" class="{{ $textareaInputClass }}">{{ old('description', $isEdit ? $screen->description : '') }}</x-textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            </div>
                        </section>
                    </aside>
                </div>

                <section class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('Network Access') }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Whitelist restricts access. Blacklist always denies access first.') }}</p>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
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
                </section>
            </div>
        </div>

        <div class="mx-auto mt-2 h-1.5 w-20 rounded-full bg-[#444444]"></div>
        <div class="mx-auto mt-0.5 h-2 w-28 rounded-full bg-[#525252]"></div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-gray-200 pt-4">
        <x-secondary-link href="{{ route('screens.index') }}">{{ __('Cancel') }}</x-secondary-link>
        <x-primary-button>{{ $submitLabel }}</x-primary-button>
    </div>
</form>
