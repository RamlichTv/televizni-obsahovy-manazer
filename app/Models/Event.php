<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores one audit/event stream entry with optional links to related domain models.
 */
class Event extends Model
{
    /**
     * Mass-assignable columns used by the event logger.
     *
     * @var array<int, string>
     */
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

    /**
     * Attribute casting for structured event metadata.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Resolve the event type describing what happened.
     */
    public function type() : BelongsTo {
        return $this->belongsTo(EventType::class, 'type_id');
    }

    /**
     * Resolve the acting user, including soft-deleted accounts for historic accuracy.
     */
    public function user() : BelongsTo {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Resolve the affected screen when the event is screen-related.
     */
    public function screen() : BelongsTo {
        return $this->belongsTo(Screen::class);
    }

    /**
     * Resolve the related content object, including archived/deleted history.
     */
    public function content() : BelongsTo {
        return $this->belongsTo(ContentObject::class, 'related_content')->withTrashed();
    }

    /**
     * Resolve the related profile, including soft-deleted profiles for history views.
     */
    public function profile() : BelongsTo {
        return $this->belongsTo(Profile::class, 'related_profile')->withTrashed();
    }

    /**
     * Resolve the related module when the event targets module management.
     */
    public function module() : BelongsTo {
        return $this->belongsTo(Module::class, 'related_module');
    }

    /**
     * Resolve the related role for ACL-focused entries.
     */
    public function role() : BelongsTo {
        return $this->belongsTo(Role::class, 'related_role');
    }

    /**
     * Resolve the related tag for content tagging events.
     */
    public function tag() : BelongsTo {
        return $this->belongsTo(Tag::class, 'related_tag');
    }

    /**
     * Resolve the related content type when type configuration changed.
     */
    public function contentType() : BelongsTo {
        return $this->belongsTo(ContentType::class, 'related_content_type');
    }

    /**
     * Resolve the related module category for module taxonomy changes.
     */
    public function moduleCategory() : BelongsTo {
        return $this->belongsTo(ModuleCategory::class, 'related_module_category');
    }

    /**
     * Resolve the related profile page when layout actions are logged.
     */
    public function page() : BelongsTo {
        return $this->belongsTo(Page::class, 'related_page');
    }

    /**
     * Resolve the related page module placement for layout placement changes.
     */
    public function pageModule() : BelongsTo {
        return $this->belongsTo(PageModule::class, 'related_page_module');
    }

    /**
     * Resolve the affected user account, including soft-deleted users for audits.
     */
    public function relatedUser() : BelongsTo {
        return $this->belongsTo(User::class, 'related_user')->withTrashed();
    }
}
