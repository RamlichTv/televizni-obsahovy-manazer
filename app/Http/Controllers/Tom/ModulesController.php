<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ModulesController extends Controller
{
    public function index() : View {
        return view('services.modules.index');
    }
}
