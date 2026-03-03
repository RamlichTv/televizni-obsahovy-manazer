<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Profile extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'version',
        'settings',
        'created_by',
    ];

    protected $casts = [
        'settings' => 'array',
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
     * Pages.
     *
     * @return HasMany
     */
    public function pages() : HasMany {
        return $this->hasMany(Page::class)->orderBy('order')->orderBy('id');
    }

    /**
     * Created by.
     *
     * @return BelongsTo
     */
    public function createdBy() : BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

