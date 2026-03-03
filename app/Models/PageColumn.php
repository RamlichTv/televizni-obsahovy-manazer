<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageColumn extends Model
{
    protected $guarded = [];

    /**
     * Page.
     *
     * @return BelongsTo
     */
    public function page() : BelongsTo {
        return $this->belongsTo(Page::class);
    }
}

