<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'blade_component',
        'config_schema',
    ];

    protected $casts = [
        'config_schema' => 'array',
    ];

    public function pages() : HasMany {
        return $this->hasMany(PageModule::class);
    }

    public function createdBy() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function categories() : BelongsToMany {
        return $this->belongsToMany(ModuleCategory::class, 'module_categories');
    }
}
