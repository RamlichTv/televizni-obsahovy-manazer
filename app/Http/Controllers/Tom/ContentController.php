<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function index() {
        return view('content.index');
    }
}
