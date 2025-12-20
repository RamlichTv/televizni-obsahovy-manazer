<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'message',
        'type',
        'user_id',
        'screen_id',
        'related_content',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function type() : BelongsTo {
        return $this->belongsTo(EventType::class, 'type');
    }

    public function user() : BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function screen() : BelongsTo {
        return $this->belongsTo(Screen::class);
    }

    public function content() : BelongsTo {
        return $this->belongsTo(ContentObject::class, 'related_content');
    }
}
