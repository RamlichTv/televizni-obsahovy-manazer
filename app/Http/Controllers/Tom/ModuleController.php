<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index() {
        return view('modules.index');
    }
}
