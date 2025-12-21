<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageModule extends Model
{
    protected $fillable = [
        'page_id',
        'module_id',
        'row_start',
        'row_span',
        'col_start',
        'col_span',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function page() : BelongsTo {
        return $this->belongsTo(Page::class);
    }

    public function module() : BelongsTo {
        return $this->belongsTo(Module::class);
    }
}
