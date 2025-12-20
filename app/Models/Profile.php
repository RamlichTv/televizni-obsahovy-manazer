<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Profile extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'grid_rows',
        'grid_columns',
    ];

    protected static function booted() : void {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    public function pages() : HasMany {
        return $this->hasMany(Page::class);
    }

    public function createdBy() : BelongsTo {
        return $this->belongsTo(User::class);
    }
}
