<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'profile_id',
        'name',
        'order',
        'row_count',
        'column_count',
        'is_active',
        'duration',
    ];

    protected $casts = [
        'row_count' => 'integer',
        'column_count' => 'integer',
        'is_active' => 'boolean',
        'duration' => 'integer',
    ];

    /**
     * Profile.
     *
     * @return BelongsTo
     */
    public function profile() : BelongsTo {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Modules.
     *
     * @return HasMany
     */
    public function modules() : HasMany {
        return $this->hasMany(PageModule::class)->orderBy('order')->orderBy('id');
    }

    /**
     * Rows.
     *
     * @return HasMany
     */
    public function rows() : HasMany {
        return $this->hasMany(PageRow::class)->orderBy('index');
    }

    /**
     * Columns.
     *
     * @return HasMany
     */
    public function columns() : HasMany {
        return $this->hasMany(PageColumn::class)->orderBy('index');
    }
}

