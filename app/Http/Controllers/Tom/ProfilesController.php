<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilesController extends Controller
{
    public function index() : View {
        return view('services.profiles.index');
    }

    public function show(Profile $profile) : View {
        return view('services.profiles.show', ['profile' => $profile]);
    }

    public function create() : View {
        return view('services.profiles.create');
    }

    public function store(Request $request) {
        //
    }

    public function edit(Profile $profile) : View {
        return view('services.profiles.edit', ['profile' => $profile]);
    }

    public function update(Request $request, Profile $profile) {
        //
    }

    public function destroy(Request $request, Profile $profile) {
        //
    }
}
