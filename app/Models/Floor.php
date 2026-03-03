<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    protected $fillable = ['building_id', 'name'];

    /**
     * Building.
     *
     * @return BelongsTo
     */
    public function building() : BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Rooms.
     *
     * @return HasMany
     */
    public function rooms() : HasMany
    {
        return $this->hasMany(Room::class);
    }
}

