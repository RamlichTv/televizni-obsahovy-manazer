<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LoadingScreen extends Component
{
    /**
     * Render the associated view output.
     *
     * @return View|Closure|string
     */
    public function render(): View|Closure|string
    {
        return view('components.loading-screen');
    }
}
