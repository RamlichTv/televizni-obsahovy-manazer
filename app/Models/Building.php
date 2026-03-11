<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Building extends Model
{
    protected $fillable = ['name', 'address'];

    /**
     * Rooms.
     *
     * @return HasManyThrough
     */
    public function rooms() : HasManyThrough
    {
        return $this->hasManyThrough(Room::class, Floor::class, 'building_id', 'floor_id');
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
