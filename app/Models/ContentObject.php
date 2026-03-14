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
     * Presentation-friendly attributes exposed to module templates.
     *
     * These keep DB-backed HTML templates readable by avoiding deep `meta.*`
     * lookups for common media and label values.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'file_url',
        'file_items',
        'primary_file',
        'media_url',
        'media_mime_type',
        'media_is_image',
        'media_is_video',
        'media_is_pdf',
        'has_media',
        'type_name',
        'author_name',
        'status_name',
        'tag_names',
        'published_label',
        'excerpt',
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
        return $this->normalizeMetaPath($meta['path'] ?? null);
    }

    /**
     * Public URL for the stored file.
     *
     * @return string|null
     */
    public function fileUrl() : ?string
    {
        $path = $this->normalizeMetaPath(data_get($this->meta, 'path'));
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

    /**
     * Accessor for module template serialization.
     *
     * @return string|null
     */
    public function getFileUrlAttribute() : ?string
    {
        return $this->fileUrl();
    }

    /**
     * Normalize all file entries from content meta.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fileItems() : array
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $rawItems = $meta['files'] ?? [];
        $items = [];

        if (is_array($rawItems)) {
            foreach ($rawItems as $rawItem) {
                if (!is_array($rawItem)) {
                    continue;
                }

                $url = $this->resolveMetaPathToUrl($rawItem['path'] ?? null);
                if ($url === null) {
                    continue;
                }

                $items[] = [
                    'path' => $rawItem['path'] ?? null,
                    'url' => $url,
                    'original_filename' => $rawItem['original_filename'] ?? null,
                    'mime_type' => $rawItem['mime_type'] ?? null,
                    'size' => isset($rawItem['size']) ? (int) $rawItem['size'] : null,
                ];
            }
        }

        if ($items === []) {
            $singleUrl = $this->fileUrl();
            if ($singleUrl !== null) {
                $items[] = [
                    'path' => $meta['path'] ?? null,
                    'url' => $singleUrl,
                    'original_filename' => $meta['original_filename'] ?? null,
                    'mime_type' => $meta['mime_type'] ?? null,
                    'size' => isset($meta['size']) ? (int) $meta['size'] : null,
                ];
            }
        }

        return $items;
    }

    /**
     * Accessor for module template serialization.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFileItemsAttribute() : array
    {
        return $this->fileItems();
    }

    /**
     * First resolved file item for templates that only need one visual.
     *
     * @return array<string, mixed>|null
     */
    public function getPrimaryFileAttribute() : ?array
    {
        return $this->fileItems()[0] ?? null;
    }

    /**
     * Resolved media URL used by visual module templates.
     *
     * @return string|null
     */
    public function getMediaUrlAttribute() : ?string
    {
        return $this->getPrimaryFileAttribute()['url'] ?? $this->fileUrl();
    }

    /**
     * Resolved media MIME type used by visual module templates.
     *
     * @return string|null
     */
    public function getMediaMimeTypeAttribute() : ?string
    {
        $primaryFile = $this->getPrimaryFileAttribute();
        $mimeType = trim((string) ($primaryFile['mime_type'] ?? data_get($this->meta, 'mime_type', '')));

        return $mimeType !== '' ? strtolower($mimeType) : null;
    }

    /**
     * Whether the primary media is an image.
     *
     * @return bool
     */
    public function getMediaIsImageAttribute() : bool
    {
        return $this->detectMediaKind() === 'image';
    }

    /**
     * Whether the primary media is a video.
     *
     * @return bool
     */
    public function getMediaIsVideoAttribute() : bool
    {
        return $this->detectMediaKind() === 'video';
    }

    /**
     * Whether the primary media is a PDF.
     *
     * @return bool
     */
    public function getMediaIsPdfAttribute() : bool
    {
        return $this->detectMediaKind() === 'pdf';
    }

    /**
     * Whether the content object exposes any visual media.
     *
     * @return bool
     */
    public function getHasMediaAttribute() : bool
    {
        return $this->getMediaUrlAttribute() !== null;
    }

    /**
     * Flatten related type name for templates.
     *
     * @return string
     */
    public function getTypeNameAttribute() : string
    {
        return trim((string) ($this->type?->name ?? ''));
    }

    /**
     * Flatten related author name for templates.
     *
     * @return string
     */
    public function getAuthorNameAttribute() : string
    {
        return trim((string) ($this->author?->name ?? ''));
    }

    /**
     * Flatten related status name for templates.
     *
     * @return string
     */
    public function getStatusNameAttribute() : string
    {
        return trim((string) ($this->status?->name ?? ''));
    }

    /**
     * Flatten related tag names for templates.
     *
     * @return array<int, string>
     */
    public function getTagNamesAttribute() : array
    {
        return $this->tags
            ->pluck('name')
            ->map(static fn ($name): string => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Human-friendly published timestamp for Mustache templates.
     *
     * @return string
     */
    public function getPublishedLabelAttribute() : string
    {
        return $this->published_at?->format('d.m.Y H:i') ?? '';
    }

    /**
     * Short summary text for compact visual layouts.
     *
     * @return string
     */
    public function getExcerptAttribute() : string
    {
        return trim((string) Str::limit((string) ($this->description ?? ''), 160));
    }

    /**
     * Resolve one meta path to a public URL.
     *
     * @param string|null $rawPath
     * @return string|null
     */
    private function resolveMetaPathToUrl(?string $rawPath) : ?string
    {
        $normalizedPath = $this->normalizeMetaPath($rawPath);
        if ($normalizedPath === null) {
            return null;
        }

        if (filter_var($normalizedPath, FILTER_VALIDATE_URL)) {
            return $normalizedPath;
        }

        return Storage::disk('public')->exists($normalizedPath)
            ? asset('storage/' . $normalizedPath)
            : null;
    }

    /**
     * Detect the primary media kind used by templates.
     *
     * @return string|null
     */
    private function detectMediaKind() : ?string
    {
        $url = strtolower((string) ($this->getMediaUrlAttribute() ?? ''));
        $mimeType = strtolower((string) ($this->getMediaMimeTypeAttribute() ?? ''));
        $name = strtolower((string) ($this->getPrimaryFileAttribute()['original_filename'] ?? ''));

        if ($mimeType !== '') {
            if (str_starts_with($mimeType, 'image/')) {
                return 'image';
            }

            if (str_starts_with($mimeType, 'video/')) {
                return 'video';
            }

            if ($mimeType === 'application/pdf') {
                return 'pdf';
            }
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
        if (str_ends_with($path, '.pdf') || str_ends_with($name, '.pdf')) {
            return 'pdf';
        }

        foreach (['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp'] as $extension) {
            if (str_ends_with($path, $extension) || str_ends_with($name, $extension)) {
                return 'image';
            }
        }

        foreach (['.mp4', '.webm', '.ogg', '.mov'] as $extension) {
            if (str_ends_with($path, $extension) || str_ends_with($name, $extension)) {
                return 'video';
            }
        }

        return null;
    }

    /**
     * Normalize one raw meta path value.
     *
     * @param string|null $rawPath
     * @return string|null
     */
    private function normalizeMetaPath(?string $rawPath) : ?string
    {
        $rawPath = trim((string) $rawPath);
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
}
