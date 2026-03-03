<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentObject extends Model
{
    use HasUuids, SoftDeletes;
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'type_id',
        'author_id',
        'description',
        'priority',
        'status_id',
        'meta',
        'published_at',
        'expires_at',
        'archived_at'
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
        'published_at' => 'datetime',
        'archived_at' => 'datetime'
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
     * Type.
     *
     * @return BelongsTo
     */
    public function type() : BelongsTo {
        return $this->belongsTo(ContentType::class, 'type_id');
    }

    /**
     * Author.
     *
     * @return BelongsTo
     */
    public function author() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * Events.
     *
     * @return HasMany
     */
    public function events() : HasMany {
        return $this->hasMany(Event::class, 'related_content');
    }

    /**
     * Tags.
     *
     * @return BelongsToMany
     */
    public function tags() : BelongsToMany {
        return $this->belongsToMany(Tag::class, 'content_tags');
    }

    /**
     * Status.
     *
     * @return BelongsTo
     */
    public function status() : BelongsTo {
        return $this->belongsTo(ContentStatus::class);
    }

    /**
     * File path relative to the public disk root.
     *
     * Supports legacy values that were saved as "/storage/...".
     *
     * @return string|null
     */
    public function filePath() : ?string
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $rawPath = trim((string) ($meta['path'] ?? ''));
        if ($rawPath === '') {
            return null;
        }

        if (filter_var($rawPath, FILTER_VALIDATE_URL)) {
            return $rawPath;
        }

        $path = ltrim($rawPath, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        return $path !== '' ? $path : null;
    }

    /**
     * Public URL for the stored file.
     *
     * @return string|null
     */
    public function fileUrl() : ?string
    {
        $path = $this->filePath();
        if ($path === null) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return Storage::disk('public')->exists($path)
            ? asset('storage/' . $path)
            : null;
    }
}
