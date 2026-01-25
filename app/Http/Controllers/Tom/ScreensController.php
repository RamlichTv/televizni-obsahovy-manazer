<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use App\Models\Screen;
use Illuminate\View\View;
use Illuminate\Http\Request;

class ScreensController extends Controller
{
    public function index() : View {
        return view('services.screens.index');
    }

    public function show(Screen $screen) : View {
        return view('services.screens.show', ['screen' => $screen]);
    }

    public function create() : View {
        return view('services.screens.create');
    }

    public function store(Request $request) {
        //
    }

    public function edit(Screen $screen) : View {
        return view('services.screens.edit', ['screen' => $screen]);
    }

    public function update(Request $request, Screen $screen) {
        //
    }

    public function destroy(Request $request, Screen $screen) {
        //
    }
}
