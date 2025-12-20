<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ModuleCategory extends Model
{
    protected $fillable = ['name'];

    public function modules() : BelongsToMany {
        return $this->belongsToMany(Module::class, 'module_categories');
    }
}
