<?php

namespace App\Domains\Common\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 도메인 공통 인앱 알림함 모델.
 * aggregation_key/open_aggregation_key로 unread 알림 묶음을 관리한다.
 */
final class NotificationInbox extends Model
{
    public const RECIPIENT_USER = 'USER';

    public const RECIPIENT_STAFF = 'STAFF';

    public const ACTOR_USER = 'USER';

    public const ACTOR_STAFF = 'STAFF';

    public const EVENT_CHAT_MESSAGE_CREATED = 'chat.message.created';

    public const EVENT_HOSPITAL_STATUS_CHANGE_REQUESTED = 'hospital.status_change.requested';

    public const EVENT_HOSPITAL_STATUS_CHANGE_PROCESSED = 'hospital.status_change.processed';

    public const TARGET_CHAT = 'chat';

    public const TARGET_HOSPITAL_STATUS_CHANGE_REQUEST = 'hospital_status_change_request';

    protected $table = 'notification_inboxes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'actor_type',
        'actor_id',
        'event_type',
        'title',
        'body',
        'aggregation_key',
        'open_aggregation_key',
        'event_count',
        'target_type',
        'target_id',
        'payload',
        'read_at',
    ];

    protected $casts = [
        'recipient_id' => 'integer',
        'actor_id' => 'integer',
        'event_count' => 'integer',
        'target_id' => 'integer',
        'payload' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'event_count' => 1,
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class, 'notification_inbox_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
