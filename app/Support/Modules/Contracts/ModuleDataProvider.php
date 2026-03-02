<?php

namespace App\Support\Modules\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface ModuleDataProvider
{
    /**
     * Resolve safe payload for a module.
     *
     * @param array $source
     * @param array $config
     * @param Authenticatable|null $viewer
     * @return array
     */
    public function resolve(array $source, array $config, ?Authenticatable $viewer = null): array;
}

