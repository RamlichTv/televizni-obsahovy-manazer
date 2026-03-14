<?php

/**
 * Content tag manager controller.
 *
 * The feedback refresh keeps tag CRUD redirects pointed back at the tag manager
 * so the shared modal shell can reopen with the correct context intact.
 */

namespace App\Http\Controllers\Tom\Services\Content;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TagsController extends Controller
{
    /**
     * Stores a new tag.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('tagManager', [
            'name' => ['required', 'string', 'alpha:utf-8', 'max:20', 'unique:tags,name'],
        ]);

        $tag = Tag::create([
            'name' => strtolower($validated['name'])
        ]);

        $this->logEvent(
            typeName: 'tag_created',
            message: 'Tag #' . $tag->name . ' was created.',
            context: [
                'action' => 'content.tags.store',
                'tag_id' => $tag->id,
                'tag_name' => $tag->name,
            ],
            userId: $request->user()?->id,
        );

        return back()
            ->with('openContentManager', 'tags')
            ->with('success', __('Tag created successfully.'));
    }

    /**
     * Deletes the current tag.
     *
     * @param Tag $tag
     * @return RedirectResponse
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tagName = $tag->name;
        $tagId = $tag->id;
        $tag->content_objects()->detach();

        $tag->delete();

        $this->logEvent(
            typeName: 'tag_deleted',
            message: 'Tag #' . $tagName . ' was deleted.',
            context: [
                'action' => 'content.tags.destroy',
                'tag_id' => $tagId,
                'tag_name' => $tagName,
            ],
            userId: request()->user()?->id,
        );

        return back()
            ->with('openContentManager', 'tags')
            ->with('success', __('Tag deleted successfully.'));
    }
}
