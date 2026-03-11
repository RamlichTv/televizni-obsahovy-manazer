{{--
    Overview:
    This view composes the components > services > users > form > direct-permissions interface, rendering collapsible permission groups with reactive selection counters.
--}}

@props([
    'permissionGroupsPrepared' => collect(),
    'totalPermissionsCount' => 0,
])

<div class="rounded-xl border border-gray-200 bg-white p-4 transition-all duration-300 hover:border-[#5EB4EE]/50">
    {{-- Top-level section toggle controls the full direct-permissions block visibility. --}}
    <button
        type="button"
        class="flex w-full items-center justify-between gap-3 text-left transition-colors duration-200 hover:text-[#077CB6]"
        @click="permissionsOpen = !permissionsOpen"
    >
        <span>
            <span class="block text-base font-semibold text-gray-900">{{ __('Direct Permissions') }}</span>
            <span class="block text-xs text-gray-500">{{ __('Grouped by domain, each group can be collapsed.') }}</span>
        </span>
        <span class="inline-flex items-center gap-3">
            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600">
                <span x-text="selectedPermissionIds.length"></span> / {{ $totalPermissionsCount }}
            </span>
            <x-icon name="arr" class="h-4 w-4 text-gray-500 transition-transform" x-bind:class="permissionsOpen ? 'rotate-180' : ''" />
        </span>
    </button>

    <div
        x-show="permissionsOpen"
        x-transition:enter="ease-out duration-250"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="ease-in duration-180"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="mt-4 space-y-3"
    >
        @foreach ($permissionGroupsPrepared as $groupKey => $group)
            {{-- Group card highlights when at least one permission in this group is selected. --}}
            <div
                class="rounded-lg border border-gray-200 bg-gray-50 transition-all duration-200 hover:border-[#5EB4EE]/50"
                x-bind:class="groupSelectedCount('{{ $groupKey }}') > 0 ? 'border-[#5EB4EE]/70 bg-[#5EB4EE]/5' : ''"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition-colors duration-200 hover:text-[#077CB6]"
                    @click="togglePermissionGroup('{{ $groupKey }}')"
                >
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">{{ __($group['name']) }}</span>
                        <span class="block text-xs text-gray-500">{{ __('Permission Group') }}</span>
                    </span>
                    <span class="inline-flex items-center gap-3">
                        <span class="rounded-full bg-white px-2 py-1 text-xs text-gray-600 border border-gray-200">
                            <span x-text="groupSelectedCount('{{ $groupKey }}')"></span> / {{ $group['permissions']->count() }}
                        </span>
                        <x-icon name="arr" class="h-4 w-4 text-gray-500 transition-transform" x-bind:class="permissionGroupOpen['{{ $groupKey }}'] ? 'rotate-180' : ''" />
                    </span>
                </button>

                <div
                    x-show="permissionGroupOpen['{{ $groupKey }}']"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="px-3 pb-3 space-y-2"
                >
                    @foreach ($group['permissions'] as $permission)
                        @php
                            // Normalize to string because Alpine checkbox binding stores string values.
                            $permissionId = (string) $permission->id;
                            $permissionLabel = __($permission->name);
                            if ($permissionLabel === $permission->name) {
                                $permissionLabel = \Illuminate\Support\Str::of($permission->name)->replace('_', ' ')->title();
                            }
                        @endphp
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-white px-3 py-2 cursor-pointer transition-all duration-200 hover:border-[#5EB4EE]/60 hover:bg-[#5EB4EE]/5">
                            <span class="text-sm text-gray-800">{{ $permissionLabel }}</span>
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permissionId }}"
                                class="rounded border-gray-300 text-[#077CB6] shadow-sm focus:ring-[#5EB4EE]"
                                x-model="selectedPermissionIds"
                            >
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <x-input-error class="mt-2" :messages="$errors->get('permissions')" />
    <x-input-error class="mt-2" :messages="$errors->get('permissions.*')" />
</div>
