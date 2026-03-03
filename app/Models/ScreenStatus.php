<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScreenStatus extends Model
{
    protected $table = 'status_types';

    protected $fillable = ['name'];

    /**
     * Screens.
     *
     * @return HasMany
     */
    public function screens() : HasMany
    {
        return $this->hasMany(Screen::class, 'status_id');
    }
}

