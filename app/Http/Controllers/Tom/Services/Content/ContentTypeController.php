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
     * Store a newly created resource.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:content_types,name'],
            'is_file' => ['sometimes', 'boolean'],
            'extensions' => ['nullable', 'string'],
            'max_size_mb' => ['nullable', 'numeric', 'min:1', 'max:1024'],
        ]);

        $type = ContentType::create($this->prepareData($request));

        $this->logEvent(
            typeName: 'content_type_created',
            message: 'Content type "' . $type->name . '" was created.',
            context: [
                'action' => 'content.types.store',
                'type_id' => $type->id,
                'is_file' => (bool) $type->is_file,
            ],
            userId: $request->user()?->id,
        );

        return back()->with('success', __('Content type created successfully.'));
    }

    /**
     * Update the current resource.
     *
     * @param Request $request
     * @param ContentType $type
     * @return RedirectResponse
     */
    public function update(Request $request, ContentType $type): RedirectResponse
    {
        $before = [
            'name' => $type->name,
            'is_file' => (bool) $type->is_file,
            'extension_whitelist' => $type->extension_whitelist,
            'max_size' => $type->max_size,
        ];

        $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('content_types')->ignore($type->id)],
            'is_file' => ['sometimes', 'boolean'],
            'extensions' => ['nullable', 'string'],
            'max_size_mb' => ['nullable', 'numeric', 'min:1', 'max:1024'],
        ]);

        $type->update($this->prepareData($request));

        $this->logEvent(
            typeName: 'content_type_edited',
            message: 'Content type "' . $type->name . '" was updated.',
            context: [
                'action' => 'content.types.update',
                'type_id' => $type->id,
                'is_file' => (bool) $type->is_file,
                'old_name' => $before['name'],
                'new_name' => $type->name,
                'old_is_file' => $before['is_file'],
                'new_is_file' => (bool) $type->is_file,
                'old_extension_whitelist' => $before['extension_whitelist'],
                'new_extension_whitelist' => $type->extension_whitelist,
                'old_max_size' => $before['max_size'],
                'new_max_size' => $type->max_size,
            ],
            userId: $request->user()?->id,
        );

        return back()->with('success', __('Content type updated successfully.'));
    }

    /**
     * Delete the current resource.
     *
     * @param ContentType $type
     * @return RedirectResponse
     */
    public function destroy(ContentType $type): RedirectResponse
    {
        // Guard: do not delete a type if any content uses it.
        if ($type->content_objects()->exists()) {
            $this->logFailure(
                message: 'Content type deletion failed because type is used by existing content.',
                context: [
                    'action' => 'content.types.destroy',
                    'type_id' => $type->id,
                    'type_name' => $type->name,
                ],
                userId: request()->user()?->id,
            );

            return back()->with('error', __('Cannot delete type because it is used by existing content.'));
        }

        $typeName = $type->name;
        $typeId = $type->id;
        $type->delete();

        $this->logEvent(
            typeName: 'content_type_deleted',
            message: 'Content type "' . $typeName . '" was deleted.',
            context: [
                'action' => 'content.types.destroy',
                'type_id' => $typeId,
            ],
            userId: request()->user()?->id,
        );

        return back()->with('success', __('Content type deleted successfully.'));
    }

    /**
     * Prepare data.
     *
     * @param Request $request
     * @return array
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


