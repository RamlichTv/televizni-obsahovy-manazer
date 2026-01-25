<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModulesController extends Controller
{
    public function index() : View {
        return view('services.modules.index');
    }

    public function show(Module $module) : View {
        return view('services.modules.show', ['module' => $module]);
    }

    public function create() : View {
        return view('services.modules.create');
    }

    public function store(Request $request) {
        //
    }

    public function edit(Module $module) : View {
        return view('services.modules.edit', ['module' => $module]);
    }

    public function update(Request $request, Module $module) {
        //
    }

    public function destroy(Request $request, Module $module) {
        //
    }
}
