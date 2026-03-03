<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageModule extends Model
{
    protected $fillable = [
        'page_id',
        'module_id',
        'row_index',
        'column_index',
        'row_span',
        'column_span',
        'config_overrides',
        'order',
    ];

    protected $casts = [
        'row_index' => 'integer',
        'column_index' => 'integer',
        'row_span' => 'integer',
        'column_span' => 'integer',
        'order' => 'integer',
        'config_overrides' => 'array',
    ];

    /**
     * Page.
     *
     * @return BelongsTo
     */
    public function page() : BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Module.
     *
     * @return BelongsTo
     */
    public function module() : BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}

