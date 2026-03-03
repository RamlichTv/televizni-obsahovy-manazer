<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventType extends Model
{
    protected $fillable = [];

    /**
     * Severity type.
     *
     * @return BelongsTo
     */
    public function severityType() : BelongsTo {
        return $this->belongsTo(SeverityType::class, 'severity');
    }

    /**
     * Events.
     *
     * @return HasMany
     */
    public function events() : HasMany {
        return $this->hasMany(Event::class, 'type_id');
    }
}
