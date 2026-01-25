<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolesController extends Controller
{
    public function index() : View {
        return view('services.roles.index');
    }

    public function show(Role $role) : View {
        return view('services.roles.show', ['role' => $role]);
    }

    public function create() : View {
        return view('services.roles.create');
    }

    public function store(Request $request) {
        //
    }

    public function edit($role) : View {
        return view('services.roles.edit', ['role' => $role]);
    }

    public function update(Request $request, Role $role) {
        //
    }

    public function destroy(Request $request, Role $role) {
        //
    }
}
