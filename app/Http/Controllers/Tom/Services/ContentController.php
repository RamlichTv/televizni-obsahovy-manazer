<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\Services\ContentObjectRequest;
use App\Models\ContentObject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index() : View {
        return view('services.content.index', [
            'tags' => \App\Models\Tag::all(),
            'contentTypes' => \App\Models\ContentType::all(),
        ]);
    }

    public function manage(Request $request) : View {
        $content_objects = ContentObject::where('author_id', $request->user()->id);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function unpublished() : View {
        $content_objects = ContentObject::where('status_id', 1);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function library() : View {
        $content_objects = ContentObject::where('status_id', 2);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function archived() : View {
        $content_objects = ContentObject::where('status_id', 3);
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function deleted() : View {
        $content_objects = ContentObject::onlyTrashed();
        
        $this->applyFilters($content_objects);

        return view('services.content.list', [
            'content_objects' => $content_objects->latest()->paginate(20)
        ]);
    }

    public function show(ContentObject $content_object) : View {
        $content_object->load(['author', 'tags', 'type']);
        return view('services.content.show', ['content' => $content_object]);
    }

    public function create() : View {
        return view('services.content.create');
    }

    function store(ContentObjectRequest $request) {
        //
    }

    function edit(ContentObject $content_object) : View {
        $content_object->load(['tags']);
        
        return view('services.content.edit', [
            'content' => $content_object,
            'types' => \App\Models\ContentType::all(),
            'tags' => \App\Models\Tag::all(),
        ]);
    }

    function update(Request $request, ContentObject $content_object) {
        //
    }

    function publish(ContentObject $content_object) {
        //
    }

    function archive(ContentObject $content_object) {
        //
    }

    function destroy(ContentObject $content_object) {
        //
    }

    private function applyFilters($query) {
        if (request('type')) {
            $query->where('type_id', request('type'));
        }
        if (request('author')) {
            $query->where('author_id', request('author'));
        }
        if (request('tag')) {
            $query->whereHas('tags', function($q) {
                $q->where('tags.id', request('tag'));
            });
        }
        return $query;
    }
}
