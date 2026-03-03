<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\Services\ScreenRequest;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Profile;
use App\Models\Room;
use App\Models\Screen;
use App\Models\ScreenOrientation;
use App\Models\ScreenStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScreensController extends Controller
{
    /**
     * Display a listing of screens.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $screensQuery = Screen::query()
            ->with([
                'status:id,name',
                'profile:id,name,slug',
                'orientation:id,name',
                'room:id,name,building_id,floor_id',
                'room.building:id,name',
                'room.floor:id,name',
            ]);

        if ($search !== '') {
            $screensQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('hardware_id', 'like', '%' . $search . '%')
                    ->orWhereHas('profile', function ($profileQuery) use ($search): void {
                        $profileQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('room', function ($roomQuery) use ($search): void {
                        $roomQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhereHas('building', function ($buildingQuery) use ($search): void {
                                $buildingQuery->where('name', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('floor', function ($floorQuery) use ($search): void {
                                $floorQuery->where('name', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $screens = $screensQuery
            ->orderBy('name')
            ->get();

        $searchSuggestions = Screen::query()
            ->select(['name', 'hardware_id'])
            ->orderBy('name')
            ->get()
            ->flatMap(function (Screen $screen): array {
                return [
                    trim((string) $screen->name),
                    trim((string) ($screen->hardware_id ?? '')),
                ];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        return view('services.screens.index', array_merge(
            [
                'screens' => $screens,
                'filters' => [
                    'search' => $search,
                ],
                'searchSuggestions' => $searchSuggestions,
            ],
            $this->locationManagerData(),
        ));
    }

    /**
     * Display the requested screen.
     *
     * @param Screen $screen
     * @param Request $request
     * @return View
     */
    public function show(Screen $screen, Request $request): View
    {
        $ip = (string) ($request->ip() ?? '');
        $whitelist = collect((array) ($screen->ip_whitelist ?? []))
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->values()
            ->all();
        $blacklist = collect((array) ($screen->ip_blacklist ?? []))
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        if ($ip !== '' && in_array($ip, $blacklist, true)) {
            abort(403);
        }

        if ($whitelist !== [] && ($ip === '' || !in_array($ip, $whitelist, true))) {
            abort(403);
        }

        $screen->loadMissing([
            'status:id,name',
            'orientation:id,name',
            'room:id,name,building_id,floor_id',
            'room.building:id,name',
            'room.floor:id,name',
            'profile:id,name,slug,settings',
            'profile.pages' => fn ($query) => $query->orderBy('order')->orderBy('id'),
            'profile.pages.rows' => fn ($query) => $query->orderBy('index'),
            'profile.pages.columns' => fn ($query) => $query->orderBy('index'),
            'profile.pages.modules' => fn ($query) => $query->orderBy('order')->orderBy('id'),
            'profile.pages.modules.module.moduleCategory:id,name,slug,is_widget,is_system',
        ]);

        return view('services.screens.show', ['screen' => $screen]);
    }

    /**
     * Show the screen creation form.
     *
     * @return View
     */
    public function create(): View
    {
        return view('services.screens.create', $this->screenFormData());
    }

    /**
     * Store a newly created screen.
     *
     * @param ScreenRequest $request
     * @return RedirectResponse
     */
    public function store(ScreenRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $defaultStatusId = $this->resolveDefaultScreenStatusId();
        $screen = Screen::query()->create(
            $this->extractScreenPayload(
                validated: $validated,
                fallbackStatusId: $defaultStatusId,
            )
        );

        $this->logEvent(
            typeName: 'screen_created',
            message: 'Screen "' . $screen->name . '" was created.',
            context: [
                'action' => 'screen.store',
                'screen_id' => $screen->id,
                'screen_name' => $screen->name,
                'hardware_id' => $screen->hardware_id,
                'profile_id' => $screen->profile_id,
                'room_id' => $screen->room_id,
            ],
            userId: $request->user()?->id,
            screenId: $screen->id,
        );

        return redirect()
            ->route('screens.index')
            ->with('success', __('Screen created successfully.'));
    }

    /**
     * Show the screen edit form.
     *
     * @param Screen $screen
     * @return View
     */
    public function edit(Screen $screen): View
    {
        $screen->loadMissing([
            'status:id,name',
            'profile:id,name,slug',
            'orientation:id,name',
            'room:id,name,building_id,floor_id',
        ]);

        return view('services.screens.edit', array_merge(
            ['screen' => $screen],
            $this->screenFormData(),
        ));
    }

    /**
     * Update the given screen.
     *
     * @param ScreenRequest $request
     * @param Screen $screen
     * @return RedirectResponse
     */
    public function update(ScreenRequest $request, Screen $screen): RedirectResponse
    {
        $validated = $request->validated();
        $before = [
            'name' => $screen->name,
            'hardware_id' => $screen->hardware_id,
            'profile_id' => $screen->profile_id,
            'room_id' => $screen->room_id,
            'status_id' => $screen->status_id,
            'orientation_id' => $screen->orientation_id,
        ];

        $screen->update(
            $this->extractScreenPayload(
                validated: $validated,
                fallbackStatusId: $screen->status_id,
            )
        );

        $this->logEvent(
            typeName: 'screen_edited',
            message: 'Screen "' . $screen->name . '" was updated.',
            context: [
                'action' => 'screen.update',
                'screen_id' => $screen->id,
                'old_name' => $before['name'],
                'new_name' => $screen->name,
                'old_hardware_id' => $before['hardware_id'],
                'new_hardware_id' => $screen->hardware_id,
                'old_profile_id' => $before['profile_id'],
                'new_profile_id' => $screen->profile_id,
                'old_room_id' => $before['room_id'],
                'new_room_id' => $screen->room_id,
                'old_status_id' => $before['status_id'],
                'new_status_id' => $screen->status_id,
                'old_orientation_id' => $before['orientation_id'],
                'new_orientation_id' => $screen->orientation_id,
            ],
            userId: $request->user()?->id,
            screenId: $screen->id,
        );

        return redirect()
            ->route('screen.edit', ['screen' => $screen])
            ->with('success', __('Screen updated successfully.'));
    }

    /**
     * Remove the given screen.
     *
     * @param Request $request
     * @param Screen $screen
     * @return RedirectResponse
     */
    public function destroy(Request $request, Screen $screen): RedirectResponse
    {
        $screenId = $screen->id;
        $screenName = $screen->name;
        $screen->delete();

        $this->logEvent(
            typeName: 'screen_deleted',
            message: 'Screen "' . $screenName . '" was deleted.',
            context: [
                'action' => 'screen.destroy',
                'screen_id' => $screenId,
                'screen_name' => $screenName,
            ],
            userId: $request->user()?->id,
            screenId: $screenId,
        );

        return redirect()
            ->route('screens.index')
            ->with('success', __('Screen deleted successfully.'));
    }

    /**
     * Build shared datasets used by create/edit screen form.
     *
     * @return array
     */
    private function screenFormData(): array
    {
        return [
            'profiles' => Profile::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'statuses' => ScreenStatus::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'orientations' => ScreenOrientation::query()
                ->orderBy('id')
                ->get(['id', 'name']),
            'rooms' => Room::query()
                ->with([
                    'building:id,name',
                    'floor:id,name',
                ])
                ->orderBy('name')
                ->get(['id', 'name', 'building_id', 'floor_id']),
        ];
    }

    /**
     * Build shared datasets used by location manager modal.
     *
     * @return array
     */
    private function locationManagerData(): array
    {
        return [
            'buildings' => Building::query()
                ->withCount(['floors', 'rooms'])
                ->orderBy('name')
                ->get(['id', 'name', 'address']),
            'floors' => Floor::query()
                ->with('building:id,name')
                ->orderBy('name')
                ->get(['id', 'building_id', 'name']),
            'rooms' => Room::query()
                ->with([
                    'building:id,name',
                    'floor:id,name,building_id',
                ])
                ->orderBy('name')
                ->get(['id', 'building_id', 'floor_id', 'name']),
        ];
    }

    /**
     * Map validated request payload to fillable screen columns.
     *
     * @param array $validated
     * @return array
     */
    private function extractScreenPayload(array $validated, ?int $fallbackStatusId = null): array
    {
        return [
            'name' => (string) $validated['name'],
            'hardware_id' => trim((string) ($validated['hardware_id'] ?? '')) !== ''
                ? (string) $validated['hardware_id']
                : null,
            'profile_id' => $validated['profile_id'] ?? null,
            'status_id' => isset($validated['status_id']) ? (int) $validated['status_id'] : $fallbackStatusId,
            'orientation_id' => $validated['orientation_id'] ?? null,
            'room_id' => $validated['room_id'] ?? null,
            'width' => isset($validated['width']) ? (int) $validated['width'] : null,
            'height' => isset($validated['height']) ? (int) $validated['height'] : null,
            'auto_refresh_interval' => isset($validated['auto_refresh_interval']) ? (int) $validated['auto_refresh_interval'] : null,
            'is_interactive' => (bool) ($validated['is_interactive'] ?? false),
            'description' => trim((string) ($validated['description'] ?? '')) !== ''
                ? (string) $validated['description']
                : null,
            'last_seen_at' => $validated['last_seen_at'] ?? null,
            'ip_whitelist' => is_array($validated['ip_whitelist'] ?? null)
                ? $validated['ip_whitelist']
                : null,
            'ip_blacklist' => is_array($validated['ip_blacklist'] ?? null)
                ? $validated['ip_blacklist']
                : null,
        ];
    }

    /**
     * Resolve default status used when a create payload omits status_id.
     *
     * @return int|null
     */
    private function resolveDefaultScreenStatusId(): ?int
    {
        $onlineId = ScreenStatus::query()
            ->whereRaw('LOWER(name) = ?', ['online'])
            ->value('id');

        if ($onlineId !== null) {
            return (int) $onlineId;
        }

        $firstStatusId = ScreenStatus::query()
            ->orderBy('id')
            ->value('id');

        return $firstStatusId !== null ? (int) $firstStatusId : null;
    }
}
