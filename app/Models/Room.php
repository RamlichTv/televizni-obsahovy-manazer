<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $fillable = ['name'];

    public function building() : BelongsTo {
        return $this->belongsTo(Building::class);
    }
}
