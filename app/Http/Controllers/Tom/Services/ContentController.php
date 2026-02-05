<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Models\ContentObject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index() : View {
        return view('services.content.index');
    }

    public function show(ContentObject $content) : View {
        return view('services.content.show', ['content' => $content]);
    }

    public function create() : View {
        return view('services.content.create');
    }

    function store(Request $request) {
        //
    }

    function edit(ContentObject $content) : View {
        return view('services.content.edit', ['content' => $content]);
    }

    function update(Request $request, ContentObject $content) {
        //
    }

    function publish(Request $request, ContentObject $content) {
        //
    }

    function archive(Request $request, ContentObject $content) {
        //
    }

    function destroy(Request $request, ContentObject $content) {
        //
    }
}
