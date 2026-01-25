<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProfilesController extends Controller
{
    public function index() : View {
        return view('services.profiles.index');
    }
}
