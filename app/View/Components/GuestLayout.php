<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * Render the associated view output.
     *
     * @return View
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
