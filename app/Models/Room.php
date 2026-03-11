<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['floor_id', 'name'];

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
     * Human-readable location breadcrumb.
     *
     * @param string $separator
     * @return string
     */
    public function breadcrumbLabel(string $separator = ' > '): string
    {
        $parts = array_values(array_filter([
            trim((string) $this->floor?->building?->name),
            trim((string) $this->floor?->name),
            trim((string) $this->name),
        ], static fn (string $value): bool => $value !== ''));

        return implode($separator, $parts);
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
