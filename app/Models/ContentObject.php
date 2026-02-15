<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContentObject extends Model
{
    use HasUuids, SoftDeletes;
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'path',
        'content_type_id',
        'created_by',
        'is_archived',
    ];

    protected static function booted() : void {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    public function type() : BelongsTo {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }

    public function author() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function events() : HasMany {
        return $this->hasMany(Event::class, 'related_content');
    }

    public function tags() : BelongsToMany {
        return $this->belongsToMany(Tag::class, 'content_tags');
    }

    public function status() : BelongsTo {
        return $this->belongsTo(ContentStatus::class);
    }
}
