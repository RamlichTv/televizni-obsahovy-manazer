<?php

namespace App\View\Components\Modules;

use App\Models\Module;
use App\Support\Modules\ModuleRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Render extends Component
{
    /**
     * Create a new component instance.
     *
     * @param Module $module
     * @param array $config
     * @return void
     */
    public function __construct(
        public Module $module,
        public array $config = [],
    ) {
    }

    /**
     * Render the associated view output.
     *
     * @return View
     */
    public function render(): View
    {
        return app(ModuleRenderer::class)->render(
            module: $this->module,
            instanceConfig: $this->config,
            viewer: auth()->user(),
        );
    }
}

