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
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:tags,name'],
        ]);

        Tag::create([
            'name' => $validated['name']
        ]);

        return back()->with('success', __('Tag created successfully.'));
    }

    /**
     * Deletes a tag.
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->content_objects()->detach();

        $tag->delete();

        return back()->with('success', __('Tag deleted successfully.'));
    }
}
