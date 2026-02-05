<?php

namespace App\Http\Controllers\Tom\Services;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index() : View {
        return view('services.users.index');
    }

    public function show(User $user) : View {
        return view('services.users.show', ['user' => $user]);
    }

    public function create() : View {
        return view('services.users.create');
    }

    public function store(Request $request) {
        //
    }

    public function edit(User $user) : View {
        return view('services.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user) {
        //
    }

    public function destroy(Request $request, User $user) {
        //
    }
}
