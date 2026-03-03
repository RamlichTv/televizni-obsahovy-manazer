<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentStatus extends Model
{
    protected $fillable = [];

    /**
     * Content objects.
     *
     * @return HasMany
     */
    public function contentObjects() : HasMany {
        return $this->hasMany(ContentObject::class, 'status_id');
    }
}
