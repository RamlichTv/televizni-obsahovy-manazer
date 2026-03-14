<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Defines one named event category and its severity classification.
 */
class EventType extends Model
{
    /**
     * Event types are seeded and managed centrally, so no mass-assignment surface is needed.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    /**
     * Resolve the severity level attached to this event type.
     */
    public function severityType() : BelongsTo {
        return $this->belongsTo(SeverityType::class, 'severity');
    }

    /**
     * Return all event records that were emitted under this type.
     */
    public function events() : HasMany {
        return $this->hasMany(Event::class, 'type_id');
    }
}
