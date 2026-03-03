<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContentType extends Model
{
    protected $fillable = [
        'name',
        'is_file',
        'extension_whitelist',
        'max_size',
    ];

    protected $casts = [
        'extension_whitelist' => 'array',
    ];

    /**
     * Content objects.
     *
     * @return HasOne
     */
    public function content_objects() : HasOne {
        return $this->hasOne(ContentObject::class, 'type_id');
    }
}
