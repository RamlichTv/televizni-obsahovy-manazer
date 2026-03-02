<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasUuids;

    public $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'config_schema',
        'default_config',
        'data_source',
        'is_active',
        'revision',
        'template_path',
        'template_checksum',
        'style_path',
        'style_checksum',
        'script_path',
        'script_checksum',
        'category',
        'created_by',
    ];

    protected $casts = [
        'config_schema' => 'array',
        'default_config' => 'array',
        'data_source' => 'array',
        'is_active' => 'boolean',
        'revision' => 'integer',
    ];

    /**
     * Pages.
     *
     * @return HasMany
     */
    public function pages() : HasMany
    {
        return $this->hasMany(PageModule::class);
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
     * Module category.
     *
     * @return BelongsTo
     */
    public function moduleCategory() : BelongsTo
    {
        return $this->belongsTo(ModuleCategory::class, 'category');
    }

    /**
     * Determine whether system.
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return (bool) ($this->moduleCategory?->is_system ?? false);
    }

    /**
     * Categories.
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ModuleCategory::class, 'module_categories');
    }
}

