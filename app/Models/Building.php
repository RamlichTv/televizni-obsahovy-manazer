<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $fillable = ['name', 'address'];

    /**
     * Rooms.
     *
     * @return HasMany
     */
    public function rooms() : HasMany {
        return $this->hasMany(Room::class);
    }

    /**
     * Floors.
     *
     * @return HasMany
     */
    public function floors() : HasMany
    {
        return $this->hasMany(Floor::class);
    }
}

