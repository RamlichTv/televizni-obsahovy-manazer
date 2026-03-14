<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stores the severity buckets used to style and filter event stream entries.
 */
class SeverityType extends Model
{
    /**
     * Severity rows are seeded constants, so mass assignment is intentionally disabled.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    /**
     * Return all event types classified under this severity.
     */
    public function eventTypes() : HasMany
    {
        return $this->hasMany(EventType::class, 'severity');
    }
}
