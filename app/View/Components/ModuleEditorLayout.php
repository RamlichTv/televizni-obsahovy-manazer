<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Full-screen layout component for module authoring pages.
 *
 * The backing Blade view now lives under the `layouts` directory,
 * so this class keeps the familiar component tag API while
 * intentionally rendering `layouts.module-editor`.
 */
class ModuleEditorLayout extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $title = null,
    ) {
    }

    /**
     * Render the associated view output.
     */
    public function render(): View
    {
        return view('layouts.module-editor');
    }
}
