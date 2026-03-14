<x-app-layout title="{{ __('Events') }}">
    @php
        $severityClassMap = [
            'info' => 'bg-sky-100 text-sky-800',
            'notice' => 'bg-emerald-100 text-emerald-800',
            'warning' => 'bg-amber-100 text-amber-800',
            'critical' => 'bg-red-100 text-red-800',
        ];

        // Humanize snake_case keys from event context payloads.
        $formatKey = static function (string $key): string {
            return ucfirst(str_replace('_', ' ', $key));
        };

        // Recursively render scalar/array context values into readable HTML blocks.
        $renderValue = static function (mixed $value) use (&$renderValue, $formatKey): string {
            if (is_array($value)) {
                if ($value === []) {
                    return '<span class="text-gray-400">[]</span>';
                }

                $isAssoc = array_keys($value) !== range(0, count($value) - 1);
                $items = [];

                foreach ($value as $k => $v) {
                    $label = $isAssoc
                        ? '<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">' . e($formatKey((string) $k)) . '</span>'
                        : '<span class="text-xs font-semibold uppercase tracking-wide text-gray-500">#' . e((string) (((int) $k) + 1)) . '</span>';

                    $items[] = '<li class="border border-gray-200 bg-white px-3 py-2">'
                        . $label
                        . '<div class="mt-1 text-sm text-gray-900">' . $renderValue($v) . '</div>'
                        . '</li>';
                }

                return '<ul class="space-y-2">' . implode('', $items) . '</ul>';
            }

            if (is_bool($value)) {
                return $value
                    ? '<span class="bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 rounded-md">true</span>'
                    : '<span class="bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800 rounded-md">false</span>';
            }

            if ($value === null) {
                return '<span class="text-gray-400">null</span>';
            }

            if (is_numeric($value)) {
                return '<span class="font-mono text-sm">' . e((string) $value) . '</span>';
            }

            return '<span>' . e((string) $value) . '</span>';
        };

        // Treat null/empty values as ignorable in context cards.
        $isEmptyValue = static function (mixed $value): bool {
            if ($value === null) {
                return true;
            }

            if (is_string($value) && trim($value) === '') {
                return true;
            }

            if (is_array($value) && $value === []) {
                return true;
            }

            return false;
        };

        // Convert known context pair conventions into diff rows.
        $buildDiffData = static function (array $context) use ($formatKey, $isEmptyValue): array {
            $rows = [];
            $pairKeys = [];
            $unchangedPairKeys = [];
            $seen = [];

            foreach ($context as $key => $value) {
                if (str_starts_with((string) $key, 'old_')) {
                    $field = substr((string) $key, 4);
                    $newKey = 'new_' . $field;
                    if (!array_key_exists($newKey, $context)) {
                        continue;
                    }

                    $signature = 'oldnew:' . $field;
                    if (isset($seen[$signature])) {
                        continue;
                    }
                    $seen[$signature] = true;
                    $pairKeys[] = (string) $key;
                    $pairKeys[] = $newKey;

                    if ($context[$newKey] !== $value) {
                        $rows[] = [
                            'field' => $formatKey((string) $field),
                            'before' => $value,
                            'after' => $context[$newKey],
                        ];
                    } else {
                        $unchangedPairKeys[] = (string) $key;
                        $unchangedPairKeys[] = $newKey;
                    }

                    continue;
                }

                if (str_starts_with((string) $key, 'added_')) {
                    $field = substr((string) $key, 6);
                    $removedKey = 'removed_' . $field;
                    if (!array_key_exists($removedKey, $context)) {
                        continue;
                    }

                    $signature = 'addremove:' . $field;
                    if (isset($seen[$signature])) {
                        continue;
                    }
                    $seen[$signature] = true;
                    $pairKeys[] = (string) $key;
                    $pairKeys[] = $removedKey;

                    if (!$isEmptyValue($value) || !$isEmptyValue($context[$removedKey])) {
                        $rows[] = [
                            'field' => $formatKey((string) $field),
                            'before' => $context[$removedKey],
                            'after' => $value,
                        ];
                    } else {
                        $unchangedPairKeys[] = (string) $key;
                        $unchangedPairKeys[] = $removedKey;
                    }
                }
            }

            return [
                'rows' => $rows,
                'pair_keys' => array_values(array_unique($pairKeys)),
                'unchanged_pair_keys' => array_values(array_unique($unchangedPairKeys)),
            ];
        };

        // Precompute access flags once so each row can decide text vs. link quickly.
        $currentUser = auth()->user();
        $canAccessContentArea = $currentUser?->canAny([
            'create_content',
            'publish_content',
            'edit_content',
            'delete_content',
            'manage_archived',
            'manage_content_types',
            'manage_tags',
        ]) ?? false;
        $canAccessModulesArea = $currentUser?->canAny([
            'create_modules',
            'edit_modules',
            'delete_modules',
            'manage_module_categories',
        ]) ?? false;
        $canManageScreens = $currentUser?->can('manage_screens') ?? false;
        $canManageUsers = $currentUser?->can('manage_users') ?? false;
        $canManageAcl = $currentUser?->can('manage_acl') ?? false;

        // Resolve profile links only when model exists and actor can view it.
        $resolveProfileHref = static function (mixed $profile) use ($currentUser): ?string {
            if ($profile === null) {
                return null;
            }

            if (method_exists($profile, 'trashed') && $profile->trashed()) {
                return null;
            }

            return $currentUser?->can('view', $profile)
                ? route('profile.show', ['profile' => $profile])
                : null;
        };

        // Pages and placements are navigated through their parent profile view.
        $resolvePageHref = static function (mixed $page) use ($resolveProfileHref): ?string {
            return $resolveProfileHref($page?->profile);
        };

        $resolvePageModuleHref = static function (mixed $pageModule) use ($resolveProfileHref): ?string {
            return $resolveProfileHref($pageModule?->page?->profile);
        };
    @endphp

    <section class="flex w-full grow flex-col bg-white">
        <div class="w-full border-b border-gray-200 px-5 py-4">
            <x-services.events.filter-form
                :types="$types"
                :severity-types="$severityTypes"
                :users="$users"
                :filters="$filters"
                :search-suggestions="$searchSuggestions ?? []"
            />
        </div>

        <div class="flex grow flex-col">
            @forelse($events as $event)
                @php
                    /** @var \App\Models\Event $event */
                    $severityName = strtolower($event->type?->severityType?->name ?? 'info');
                    $severityClass = $severityClassMap[$severityName] ?? $severityClassMap['info'];
                    $eventTypeName = (string) ($event->type?->name ?? '');
                    $eventTypeLabel = $eventTypeName !== '' ? __($eventTypeName) : '';
                    if ($eventTypeLabel === $eventTypeName) {
                        $eventTypeLabel = (string) \Illuminate\Support\Str::of($eventTypeName)->replace('_', ' ')->replace('-', ' ')->title();
                    }
                    $context = (array) ($event->context ?? []);
                    $diffData = $buildDiffData($context);
                    $diffRows = $diffData['rows'];
                    // Hide raw old_/new_ keys once represented in the diff table.
                    $contextHiddenKeys = $diffRows !== []
                        ? array_merge($diffData['pair_keys'], $diffData['unchanged_pair_keys'])
                        : $diffData['unchanged_pair_keys'];
                    $contextRows = collect($context)
                        ->reject(fn ($value, $key) => in_array((string) $key, $contextHiddenKeys, true))
                        // Skip empty values so context section only shows meaningful signal.
                        ->reject(fn ($value) => $isEmptyValue($value));
                    $screenHref = $canManageScreens && $event->screen
                        ? route('screen.edit', ['screen' => $event->screen])
                        : null;
                    $contentHref = $canAccessContentArea && $event->content
                        ? route('content.show', ['content_object' => $event->content])
                        : null;
                    $profileHref = $resolveProfileHref($event->profile);
                    $moduleHref = $canAccessModulesArea && $event->module
                        ? route('module.show', ['module' => $event->module])
                        : null;
                    $roleHref = $canManageAcl && $event->role
                        ? route('role.edit', ['role' => $event->role])
                        : null;
                    $tagHref = $canAccessContentArea && $event->tag
                        ? route('content.library', ['tag_ids' => [$event->tag->id]])
                        : null;
                    $contentTypeHref = $canAccessContentArea && $event->contentType
                        ? route('content.library', ['type_ids' => [$event->contentType->id]])
                        : null;
                    $moduleCategoryHref = $canAccessModulesArea && $event->moduleCategory
                        ? route('modules.index', ['category' => $event->moduleCategory->id])
                        : null;
                    $pageHref = $resolvePageHref($event->page);
                    $pageModuleHref = $resolvePageModuleHref($event->pageModule);
                    $relatedUserHref = $canManageUsers
                        && $event->relatedUser
                        && !(method_exists($event->relatedUser, 'trashed') && $event->relatedUser->trashed())
                            ? route('users.show', ['user' => $event->relatedUser])
                            : null;
                    // If href is null, the UI renders plain text instead of a link.
                    $relatedRows = collect([
                        ['label' => __('Screen'), 'value' => $event->screen?->name, 'href' => $screenHref],
                        ['label' => __('Content'), 'value' => $event->content?->name, 'href' => $contentHref],
                        ['label' => __('Profile'), 'value' => $event->profile?->name, 'href' => $profileHref],
                        ['label' => __('Module'), 'value' => $event->module?->name, 'href' => $moduleHref],
                        ['label' => __('Role'), 'value' => $event->role?->name, 'href' => $roleHref],
                        ['label' => __('Tag'), 'value' => $event->tag?->name, 'href' => $tagHref],
                        ['label' => __('Content Type'), 'value' => $event->contentType?->name, 'href' => $contentTypeHref],
                        ['label' => __('Module Category'), 'value' => $event->moduleCategory?->name, 'href' => $moduleCategoryHref],
                        ['label' => __('Page'), 'value' => $event->page?->name, 'href' => $pageHref],
                        ['label' => __('Page Placement'), 'value' => $event->pageModule?->id, 'href' => $pageModuleHref],
                        ['label' => __('Related User'), 'value' => $event->relatedUser?->name ?: $event->relatedUser?->username, 'href' => $relatedUserHref],
                    ])->filter(fn (array $row) => $row['value'] !== null && $row['value'] !== '');
                @endphp

                <div x-data="{ open: false }" class="border-b border-gray-200 flex">
                    <div class="flex flex-col place-content-center bg-gray-100 p-2">
                        <div class="border-b-gray-300 font-semibold text-sm text-center">
                            {{ __('Event ID') }}
                        </div>
                        <div class="flex flex-col self-center place-content-center flex-1 w-18">
                            <span title="{{ $event->id }}" class="truncate text-center">{{ $event->id }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col flex-1">
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex w-full items-start gap-4 px-5 py-4 text-left transition hover:bg-gray-50"
                        >
                            <svg
                                class="mt-1 h-4 w-4 shrink-0 text-gray-500 transition-transform"
                                :class="{ 'rotate-90': open }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>

                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <p class="min-w-0 flex-1 text-base font-semibold leading-6 text-gray-900">
                                        {{ $event->message ?: __('No message') }}
                                    </p>
                                    <p class="shrink-0 text-sm text-gray-500">
                                        {{ $event->created_at?->format('d.m.Y H:i:s') }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-600">
                                    <div>
                                        <span class="inline-flex items-center rounded-md px-2.5 py-1 font-semibold {{ $severityClass }}">
                                            {{ ucfirst(__($severityName)) }}
                                        </span>
                                        <span class="rounded-md bg-gray-100 px-2.5 py-1 text-gray-800">
                                            {{ $eventTypeLabel !== '' ? $eventTypeLabel : __('Unknown type') }}
                                        </span>
                                    </div>
                                    @if($event->user)
                                        <div>
                                            <span>{{ __('Actor') }}:</span>
                                            @if($canManageUsers && !(method_exists($event->user, 'trashed') && $event->user->trashed()))
                                                <a class="text-gray-900 hover:underline" href="{{ route('users.show', ['user' => $event->user]) }}">{{ $event->user->name ?: $event->user->username }}</a>
                                            @else
                                                <span class="text-gray-900">{{ $event->user->name ?: $event->user->username }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </button>

                        <div x-show="open" x-transition class="border-t border-gray-200 bg-gray-50 px-5 py-4">
                            <div x-data="{ relatedOpen: false, diffOpen: false, contextOpen: false }" class="space-y-4">
                                @if($relatedRows->isNotEmpty())
                                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                        <button type="button" @click="relatedOpen = !relatedOpen" class="flex w-full items-center justify-between px-3 py-2 text-left">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Related Models') }}</p>
                                            <svg class="h-4 w-4 text-gray-500 transition-transform" :class="{ 'rotate-180': relatedOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="relatedOpen" x-transition class="border-t border-gray-200 p-3">
                                            <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                                @foreach($relatedRows as $row)
                                                    <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $row['label'] }}</p>
                                                        @if($row['href'] !== null)
                                                            <a href="{{ $row['href'] }}" class="mt-1 inline-flex text-sm font-medium text-gray-900 hover:underline">
                                                                {{ $row['value'] }}
                                                            </a>
                                                        @else
                                                            <p class="mt-1 text-sm font-medium text-gray-900">{{ $row['value'] }}</p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if($diffRows !== [])
                                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                        <button type="button" @click="diffOpen = !diffOpen" class="flex w-full items-center justify-between px-3 py-2 text-left">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Diff') }}</p>
                                            <svg class="h-4 w-4 text-gray-500 transition-transform" :class="{ 'rotate-180': diffOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="diffOpen" x-transition class="border-t border-gray-200 p-3">
                                            <div class="overflow-x-auto rounded-md border border-gray-200">
                                                <table class="min-w-full bg-white text-sm">
                                                    <thead class="bg-gray-100 text-left text-xs uppercase tracking-wide text-gray-600">
                                                        <tr>
                                                            <th class="px-3 py-2">{{ __('Field') }}</th>
                                                            <th class="px-3 py-2">{{ __('Before') }}</th>
                                                            <th class="px-3 py-2">{{ __('After') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($diffRows as $row)
                                                            <tr class="border-t border-gray-200">
                                                                <td class="px-3 py-2 font-semibold text-gray-800">{{ $row['field'] }}</td>
                                                                <td class="px-3 py-2 text-gray-700">{!! $renderValue($row['before']) !!}</td>
                                                                <td class="px-3 py-2 text-gray-700">{!! $renderValue($row['after']) !!}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if($contextRows->isNotEmpty())
                                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                        <button type="button" @click="contextOpen = !contextOpen" class="flex w-full items-center justify-between px-3 py-2 text-left">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Context') }}</p>
                                            <svg class="h-4 w-4 text-gray-500 transition-transform" :class="{ 'rotate-180': contextOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <div x-show="contextOpen" x-transition class="border-t border-gray-200 p-3">
                                            <div class="grid gap-3">
                                                @foreach($contextRows as $key => $value)
                                                    <div class="rounded-md border border-gray-200 bg-white p-3">
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $formatKey((string) $key) }}</p>
                                                        <div class="mt-2 text-sm text-gray-900">{!! $renderValue($value) !!}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex grow items-center justify-center px-5 py-20 text-base text-gray-500">
                    {{ __('No events found for the selected filters.') }}
                </div>
            @endforelse
        </div>

        <div class="border-t border-gray-200 px-5 py-4">
            {{ $events->links() }}
        </div>
    </section>
</x-app-layout>
