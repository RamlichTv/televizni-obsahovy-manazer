<?php

namespace App\Http\Controllers\Tom\Services\Screen;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomsController extends Controller
{
    /**
     * Return a list of rooms, optionally filtered by building/floor.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $buildingId = $request->filled('building_id') ? (int) $request->input('building_id') : null;
        $floorId = $request->filled('floor_id') ? (int) $request->input('floor_id') : null;

        $rooms = Room::query()
            ->with([
                'floor:id,name,building_id',
                'floor.building:id,name',
            ])
            ->when($buildingId !== null, fn ($query) => $query->whereHas('floor', fn ($floorQuery) => $floorQuery->where('building_id', $buildingId)))
            ->when($floorId !== null, fn ($query) => $query->where('floor_id', $floorId))
            ->orderBy('name')
            ->get(['id', 'name', 'floor_id']);

        return response()->json($rooms);
    }

    /**
     * Store a new room.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $room = Room::query()->create($this->validatePayload($request));

        if ($request->expectsJson()) {
            return response()->json($room->fresh(['floor', 'floor.building']), 201);
        }

        return redirect()
            ->route('screens.index')
            ->with('location_manager_open', true)
            ->with('success', __('Room created successfully.'));
    }

    /**
     * Update one room.
     *
     * @param Request $request
     * @param Room $room
     * @return JsonResponse|RedirectResponse
     */
    public function update(Request $request, Room $room): JsonResponse|RedirectResponse
    {
        $room->update($this->validatePayload($request));

        if ($request->expectsJson()) {
            return response()->json($room->fresh(['floor', 'floor.building']));
        }

        return redirect()
            ->route('screens.index')
            ->with('location_manager_open', true)
            ->with('success', __('Room updated successfully.'));
    }

    /**
     * Delete one room.
     *
     * @param Request $request
     * @param Room $room
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(Request $request, Room $room): JsonResponse|RedirectResponse
    {
        $room->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('screens.index')
            ->with('location_manager_open', true)
            ->with('success', __('Room deleted successfully.'));
    }

    /**
     * Validate payload.
     *
     * @param Request $request
     * @return array
     */
    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
            'floor_id' => ['required', 'integer', Rule::exists('floors', 'id')],
        ]);

        return [
            'name' => $validated['room_name'],
            'floor_id' => (int) $validated['floor_id'],
        ];
    }
}
