<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContentType extends Model
{
    /**
     * Mass-assignable content-type attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'is_file',
        'multiple_file',
        'extension_whitelist',
        'max_size',
    ];

    /**
     * Native type casts for content-type flags and JSON metadata.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_file' => 'boolean',
        'multiple_file' => 'boolean',
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
