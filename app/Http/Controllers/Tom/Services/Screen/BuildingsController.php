<?php

namespace App\Http\Controllers\Tom\Services\Screen;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BuildingsController extends Controller
{
    /**
     * Return a list of buildings.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $buildings = Building::query()
            ->withCount(['floors', 'rooms'])
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        return response()->json($buildings);
    }

    /**
     * Store a newly created building.
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $building = Building::query()->create($this->validatePayload($request));

        if ($request->expectsJson()) {
            return response()->json($building, 201);
        }

        return redirect()
            ->route('screens.index')
            ->with('location_manager_open', true)
            ->with('success', __('Building created successfully.'));
    }

    /**
     * Update one existing building.
     *
     * @param Request $request
     * @param Building $building
     * @return JsonResponse|RedirectResponse
     */
    public function update(Request $request, Building $building): JsonResponse|RedirectResponse
    {
        $building->update($this->validatePayload($request));

        if ($request->expectsJson()) {
            return response()->json($building->fresh());
        }

        return redirect()
            ->route('screens.index')
            ->with('location_manager_open', true)
            ->with('success', __('Building updated successfully.'));
    }

    /**
     * Delete one building.
     *
     * @param Request $request
     * @param Building $building
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(Request $request, Building $building): JsonResponse|RedirectResponse
    {
        $building->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('screens.index')
            ->with('location_manager_open', true)
            ->with('success', __('Building deleted successfully.'));
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
            'building_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        return [
            'name' => $validated['building_name'],
            'address' => $validated['address'] ?? null,
        ];
    }
}

