<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageRow extends Model
{
    protected $guarded = [];

    public function page() : BelongsTo {
        return $this->belongsTo(Page::class);
    }
}
