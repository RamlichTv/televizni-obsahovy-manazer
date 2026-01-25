<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Event;

class EventsController extends Controller
{
    public function index() : View {
        return view('services.events.index', ['events' => Event::all()]);
    }
}
