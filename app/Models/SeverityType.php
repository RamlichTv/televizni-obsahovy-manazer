<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeverityType extends Model
{
    protected $fillable = [];

    /**
     * Event types.
     *
     * @return HasMany
     */
    public function eventTypes() : HasMany
    {
        return $this->hasMany(EventType::class, 'severity');
    }
}
