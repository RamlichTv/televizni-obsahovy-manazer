<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScreenOrientation extends Model
{
    protected $table = 'orientation_types';
    public $timestamps = false;

    protected $fillable = ['name'];

    /**
     * Screens.
     *
     * @return HasMany
     */
    public function screens() : HasMany
    {
        return $this->hasMany(Screen::class, 'orientation_id');
    }
}

