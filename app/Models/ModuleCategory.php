<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_widget',
        'is_screensaver',
        'allow_embedded',
        'is_system',
    ];

    protected $casts = [
        'is_widget' => 'boolean',
        'is_screensaver' => 'boolean',
        'allow_embedded' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Modules.
     *
     * @return HasMany
     */
    public function modules() : HasMany
    {
        return $this->hasMany(Module::class, 'category');
    }
}
