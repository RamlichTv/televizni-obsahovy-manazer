<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ScreensController extends Controller
{
    public function index() : View {
        return view('services.screens.index');
    }
}
