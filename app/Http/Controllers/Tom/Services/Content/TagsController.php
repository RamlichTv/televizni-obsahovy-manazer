<?php

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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:tags,name'],
        ]);

        $tag = Tag::create([
            'name' => $validated['name']
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

        return back()->with('success', __('Tag created successfully.'));
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

        return back()->with('success', __('Tag deleted successfully.'));
    }
}
