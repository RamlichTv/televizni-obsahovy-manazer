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

    /**
     * Human-readable building/floor breadcrumb.
     *
     * @param string $separator
     * @return string
     */
    public function breadcrumbLabel(string $separator = ' > '): string
    {
        $parts = array_values(array_filter([
            trim((string) $this->building?->name),
            trim((string) $this->name),
        ], static fn (string $value): bool => $value !== ''));

        return implode($separator, $parts);
    }
}
