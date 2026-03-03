<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name'];

    /**
     * Content objects.
     *
     * @return BelongsToMany
     */
    public function content_objects() : BelongsToMany {
        return $this->belongsToMany(ContentObject::class, 'content_tags');
    }
}
