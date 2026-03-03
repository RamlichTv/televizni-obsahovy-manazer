<?php

namespace App\Http\Controllers\Tom\Services\Screen;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FloorsController extends Controller
{
    /**
     * Return floors, optionally scoped by building.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $buildingId = $request->filled('building_id') ? (int) $request->input('building_id') : null;

        $floors = Floor::query()
            ->when($buildingId !== null, fn ($query) => $query->where('building_id', $buildingId))
            ->orderBy('name')
            ->get(['id', 'building_id', 'name']);

        return response()->json($floors);
    }

    /**
     * Create a floor record.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $payload = $this->validatePayload($request);
        $floor = Floor::query()->create($payload);

        if ($request->expectsJson()) {
            return response()->json($floor, 201);
        }

        return redirect()
            ->route('screens.index')
            ->with('location_manager_open', true)
            ->with('success', __('Floor created successfully.'));
    }

    /**
     * Update the selected floor.
     *
     * @param Request $request
     * @param Floor $floor
     * @return JsonResponse|RedirectResponse
     */
    public function update(Request $request, Floor $floor): JsonResponse|RedirectResponse
    {
        $payload = $this->validatePayload($request, (int) $floor->id);
        $floor->update($payload);

        if ($request->expectsJson()) {
            return response()->json($floor->fresh(['building']));
        }

        return redirect()
            ->route('screens.index')
            ->with('location_manager_open', true)
            ->with('success', __('Floor updated successfully.'));
    }

    /**
     * Remove the selected floor.
     *
     * @param Request $request
     * @param Floor $floor
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(Request $request, Floor $floor): JsonResponse|RedirectResponse
    {
        $floor->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('screens.index')
            ->with('location_manager_open', true)
            ->with('success', __('Floor deleted successfully.'));
    }

    /**
     * Validate floor payload for create/update operations.
     *
     * @param Request $request
     * @param int|null $ignoreFloorId
     * @return array
     */
    private function validatePayload(Request $request, ?int $ignoreFloorId = null): array
    {
        $buildingId = (int) $request->input('building_id');
        $validated = $request->validate([
            'building_id' => ['required', 'integer', Rule::exists('buildings', 'id')],
            'floor_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('floors', 'name')
                    ->where(fn ($query) => $query->where('building_id', $buildingId))
                    ->ignore($ignoreFloorId),
            ],
        ]);

        return [
            'building_id' => (int) $validated['building_id'],
            'name' => $validated['floor_name'],
        ];
    }
}
