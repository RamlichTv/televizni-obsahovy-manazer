<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'profile_id',
        'order',
    ];

    public function profile() : BelongsTo {
        return $this->belongsTo(Profile::class);
    }

    public function modules() : HasMany {
        return $this->hasMany(PageModule::class);
    }
}
