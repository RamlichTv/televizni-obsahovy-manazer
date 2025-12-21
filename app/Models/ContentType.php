<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentType extends Model
{
    protected $fillable = [
        'name',
        'extension_whitelist',
        'max_size',
    ];

    protected $casts = [
        'extension_whitelist' => 'array',
    ];

    public function contents() : HasMany {
        return $this->hasMany(ContentObject::class);
    }
}
