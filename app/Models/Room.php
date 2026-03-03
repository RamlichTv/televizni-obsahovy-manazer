<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['building_id', 'floor_id', 'name'];

    /**
     * Building.
     *
     * @return BelongsTo
     */
    public function building() : BelongsTo {
        return $this->belongsTo(Building::class);
    }

    /**
     * Floor.
     *
     * @return BelongsTo
     */
    public function floor() : BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Screens.
     *
     * @return HasMany
     */
    public function screens() : HasMany
    {
        return $this->hasMany(Screen::class);
    }
}

