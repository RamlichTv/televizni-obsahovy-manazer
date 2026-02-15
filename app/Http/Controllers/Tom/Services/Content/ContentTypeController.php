<?php

namespace App\Http\Controllers\Tom\Services\Content;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class ContentTypeController extends Controller
{
    /**
     * Stores a new content type.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:content_types,name'],
            'is_file' => ['sometimes', 'boolean'],
            'extensions' => ['nullable', 'string'],
            'max_size_mb' => ['nullable', 'numeric', 'min:1', 'max:1024'],
        ]);

        ContentType::create($this->prepareData($request));

        return back()->with('success', __('Content type created successfully.'));
    }

    /**
     * Updates an existing type.
     */
    public function update(Request $request, ContentType $type): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('content_types')->ignore($type->id)],
            'is_file' => ['sometimes', 'boolean'],
            'extensions' => ['nullable', 'string'],
            'max_size_mb' => ['nullable', 'numeric', 'min:1', 'max:1024'],
        ]);

        $type->update($this->prepareData($request));

        return back()->with('success', __('Content type updated successfully.'));
    }

    /**
     * Deletes a type.
     */
    public function destroy(ContentType $type): RedirectResponse
    {
        // Guard: do not delete a type if any content uses it.
        if ($type->content_objects()->exists()) {
            return back()->with('error', __('Cannot delete type because it is used by existing content.'));
        }

        $type->delete();

        return back()->with('success', __('Content type deleted successfully.'));
    }

    /**
     * Helper method for formatting data before saving to the DB.
     */
    private function prepareData(Request $request): array
    {
        // 1. Process extensions (string "jpg, png" -> array ['jpg', 'png'])
        $extensions = null;
        if ($request->filled('extensions')) {
            $rawExts = explode(',', $request->input('extensions'));
            $extensions = collect($rawExts)
                ->map(fn($ext) => strtolower(trim($ext))) // Remove spaces and lowercase.
                ->filter() // Remove empty values.
                ->values()
                ->all();
        }

        // 2. Process size (MB -> bytes)
        $maxSize = null;
        if ($request->filled('max_size_mb')) {
            $maxSize = $request->input('max_size_mb') * 1024 * 1024;
        }

        return [
            'name' => $request->name,
            'is_file' => $request->boolean('is_file'), // Safely converts checkbox input to bool.
            'extension_whitelist' => $extensions,
            'max_size' => $maxSize,
        ];
    }
}
