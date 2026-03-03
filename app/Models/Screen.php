<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Screen extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'height',
        'width',
        'profile_id',
        'status_id',
        'orientation_id',
        'hardware_id',
        'room_id',
        'description',
        'is_interactive',
        'last_seen_at',
        'auto_refresh_interval',
        'ip_whitelist',
        'ip_blacklist',
    ];

    protected $casts = [
        'height' => 'integer',
        'width' => 'integer',
        'status_id' => 'integer',
        'orientation_id' => 'integer',
        'room_id' => 'integer',
        'is_interactive' => 'boolean',
        'last_seen_at' => 'datetime',
        'auto_refresh_interval' => 'integer',
        'ip_whitelist' => 'array',
        'ip_blacklist' => 'array',
    ];

    /**
     * Booted.
     *
     * @return void
     */
    protected static function booted() : void {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    /**
     * Profile.
     *
     * @return BelongsTo
     */
    public function profile() : BelongsTo {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Status.
     *
     * @return BelongsTo
     */
    public function status() : BelongsTo
    {
        return $this->belongsTo(ScreenStatus::class, 'status_id');
    }

    /**
     * Orientation.
     *
     * @return BelongsTo
     */
    public function orientation() : BelongsTo
    {
        return $this->belongsTo(ScreenOrientation::class, 'orientation_id');
    }

    /**
     * Room.
     *
     * @return BelongsTo
     */
    public function room() : BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Events.
     *
     * @return HasMany
     */
    public function events() : HasMany {
        return $this->hasMany(Event::class);
    }
}

