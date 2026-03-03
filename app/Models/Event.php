<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'message',
        'type_id',
        'user_id',
        'screen_id',
        'related_content',
        'related_profile',
        'related_module',
        'related_role',
        'related_tag',
        'related_content_type',
        'related_module_category',
        'related_page',
        'related_page_module',
        'related_user',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Type.
     *
     * @return BelongsTo
     */
    public function type() : BelongsTo {
        return $this->belongsTo(EventType::class, 'type_id');
    }

    /**
     * User.
     *
     * @return BelongsTo
     */
    public function user() : BelongsTo {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Screen.
     *
     * @return BelongsTo
     */
    public function screen() : BelongsTo {
        return $this->belongsTo(Screen::class);
    }

    /**
     * Content.
     *
     * @return BelongsTo
     */
    public function content() : BelongsTo {
        return $this->belongsTo(ContentObject::class, 'related_content')->withTrashed();
    }

    /**
     * Profile.
     *
     * @return BelongsTo
     */
    public function profile() : BelongsTo {
        return $this->belongsTo(Profile::class, 'related_profile')->withTrashed();
    }

    /**
     * Module.
     *
     * @return BelongsTo
     */
    public function module() : BelongsTo {
        return $this->belongsTo(Module::class, 'related_module');
    }

    /**
     * Role.
     *
     * @return BelongsTo
     */
    public function role() : BelongsTo {
        return $this->belongsTo(Role::class, 'related_role');
    }

    /**
     * Tag.
     *
     * @return BelongsTo
     */
    public function tag() : BelongsTo {
        return $this->belongsTo(Tag::class, 'related_tag');
    }

    /**
     * Content type.
     *
     * @return BelongsTo
     */
    public function contentType() : BelongsTo {
        return $this->belongsTo(ContentType::class, 'related_content_type');
    }

    /**
     * Module category.
     *
     * @return BelongsTo
     */
    public function moduleCategory() : BelongsTo {
        return $this->belongsTo(ModuleCategory::class, 'related_module_category');
    }

    /**
     * Page.
     *
     * @return BelongsTo
     */
    public function page() : BelongsTo {
        return $this->belongsTo(Page::class, 'related_page');
    }

    /**
     * Page module.
     *
     * @return BelongsTo
     */
    public function pageModule() : BelongsTo {
        return $this->belongsTo(PageModule::class, 'related_page_module');
    }

    /**
     * Related user.
     *
     * @return BelongsTo
     */
    public function relatedUser() : BelongsTo {
        return $this->belongsTo(User::class, 'related_user')->withTrashed();
    }
}
