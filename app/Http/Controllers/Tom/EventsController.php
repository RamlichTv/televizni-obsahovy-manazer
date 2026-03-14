<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventType;
use App\Models\SeverityType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles event stream listing, filtering, and permission-scoped visibility.
 */
class EventsController extends Controller
{
    /**
     * Render the events index with filter options and permission-aware dataset.
     *
     * Non-admin users only see event types connected to services they can operate.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        $canViewAllEventTypes = $currentUser->isAdmin();
        $visibleTypeIds = $this->resolveVisibleTypeIds($currentUser);
        // Force an always-empty match when user can view events but has no service scope.
        $restrictedTypeIds = $visibleTypeIds === [] ? [-1] : $visibleTypeIds;

        // Normalize all filter inputs before applying them to both list and filter UI state.
        $search = trim((string) $request->string('search'));
        $selectedTypeIds = $this->normalizeIntegerIds($request->input('type_ids', $request->input('type_id')));
        if (!$canViewAllEventTypes) {
            // Ignore manually supplied type filters outside of user's allowed scope.
            $selectedTypeIds = array_values(array_intersect($selectedTypeIds, $visibleTypeIds));
        }

        $selectedSeverityIds = $this->normalizeIntegerIds($request->input('severity_ids', $request->input('severity_id')));
        $selectedUserIds = $this->normalizeStringIds($request->input('user_ids', $request->input('user_id')));

        // Eager-load every model the event detail drawer can expand into.
        $events = Event::query()
            ->with([
                'type:id,name,severity',
                'type.severityType:id,name',
                'user:id,name,username',
                'screen:id,name',
                'content:id,name,type_id',
                'content.type:id,name',
                'profile:id,name,slug',
                'module:id,name',
                'role:id,name',
                'tag:id,name',
                'contentType:id,name',
                'moduleCategory:id,name,slug',
                'page:id,name,profile_id',
                'page.profile:id,name,slug',
                'pageModule:id,page_id,module_id,row_index,column_index,row_span,column_span',
                'pageModule.page:id,name,profile_id',
                'pageModule.page.profile:id,name,slug',
                'pageModule.module:id,name',
                'relatedUser:id,name,username',
            ])
            ->when(!$canViewAllEventTypes, function (Builder $query) use ($restrictedTypeIds): void {
                $query->whereIn('type_id', $restrictedTypeIds);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('message', 'like', '%' . $search . '%');
            })
            ->when($selectedTypeIds !== [], function (Builder $query) use ($selectedTypeIds): void {
                $query->whereIn('type_id', $selectedTypeIds);
            })
            ->when($selectedSeverityIds !== [], function (Builder $query) use ($selectedSeverityIds): void {
                $query->whereHas('type', function (Builder $eventTypeQuery) use ($selectedSeverityIds): void {
                    $eventTypeQuery->whereIn('severity', $selectedSeverityIds);
                });
            })
            ->when($selectedUserIds !== [], function (Builder $query) use ($selectedUserIds): void {
                $query->whereIn('user_id', $selectedUserIds);
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        // Keep auxiliary filter datasets constrained to the same visibility rules as the event list.
        return view('services.events.index', [
            'events' => $events,
            'types' => EventType::query()
                ->with('severityType:id,name')
                ->when(!$canViewAllEventTypes, function (Builder $query) use ($restrictedTypeIds): void {
                    $query->whereIn('id', $restrictedTypeIds);
                })
                ->orderBy('name')
                ->get(['id', 'name', 'severity']),
            'severityTypes' => SeverityType::query()
                ->orderBy('id')
                ->get(['id', 'name']),
            'users' => User::query()
                ->withTrashed()
                ->select(['id', 'name', 'username'])
                ->orderBy('name')
                ->get(),
            'filters' => [
                'search' => $search,
                'type_ids' => $selectedTypeIds,
                'severity_ids' => $selectedSeverityIds,
                'user_ids' => $selectedUserIds,
            ],
            'searchSuggestions' => Event::query()
                ->when(!$canViewAllEventTypes, function (Builder $query) use ($restrictedTypeIds): void {
                    $query->whereIn('type_id', $restrictedTypeIds);
                })
                ->whereNotNull('message')
                ->where('message', '!=', '')
                ->latest()
                ->limit(300)
                ->pluck('message')
                ->map(fn ($message) => trim((string) $message))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    /**
     * Normalize incoming scalar/array values into a unique, positive integer list.
     *
     * @param mixed $raw
     * @return array<int, int>
     */
    private function normalizeIntegerIds(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [$raw])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Normalize incoming scalar/array values into a unique, non-empty string list.
     *
     * @param mixed $raw
     * @return array<int, string>
     */
    private function normalizeStringIds(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [$raw])
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve event type ids visible to a user based on service permissions.
     *
     * Event type names are grouped by service prefixes (e.g. "content_"), with
     * explicit exceptions for cross-service audit entries.
     *
     * @param User $user
     * @return array<int, int>
     */
    private function resolveVisibleTypeIds(User $user): array
    {
        $prefixes = [];
        $exactNames = [];

        if ($this->userCanAny($user, [
            'create_content',
            'publish_content',
            'edit_content',
            'delete_content',
            'manage_archived',
            'manage_content_types',
            'manage_tags',
        ])) {
            $prefixes[] = 'content_';
            $prefixes[] = 'content_type_';
            $prefixes[] = 'tag_';
        }

        if ($user->can('manage_screens')) {
            $prefixes[] = 'screen_';
        }

        if ($this->userCanAny($user, [
            'create_profiles',
            'edit_profiles',
            'delete_profiles',
        ])) {
            $prefixes[] = 'profile_';
        }

        if ($this->userCanAny($user, [
            'create_modules',
            'edit_modules',
            'delete_modules',
            'manage_module_categories',
        ])) {
            $prefixes[] = 'module_';
            $prefixes[] = 'module_category_';
        }

        if ($user->can('manage_users')) {
            $prefixes[] = 'user_';
            $exactNames[] = 'role_assigned';
            $exactNames[] = 'role_removed';
        }

        if ($user->can('manage_acl')) {
            $prefixes[] = 'role_';
            $prefixes[] = 'permission_';
            $exactNames[] = 'role_assigned';
            $exactNames[] = 'role_removed';
            $exactNames[] = 'permission_assigned';
            $exactNames[] = 'permission_removed';
            $exactNames[] = 'database_accessed';
        }

        $prefixes = array_values(array_unique($prefixes));
        $exactNames = array_values(array_unique($exactNames));

        // Keep matching logic close to event type names so new types are easy to review.
        return EventType::query()
            ->get(['id', 'name'])
            ->filter(function (EventType $type) use ($prefixes, $exactNames): bool {
                $name = (string) $type->name;

                if (in_array($name, $exactNames, true)) {
                    return true;
                }

                foreach ($prefixes as $prefix) {
                    if (str_starts_with($name, $prefix)) {
                        return true;
                    }
                }

                return false;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Determine whether a user has at least one permission from the provided list.
     *
     * @param User $user
     * @param array<int, string> $permissions
     * @return bool
     */
    private function userCanAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
