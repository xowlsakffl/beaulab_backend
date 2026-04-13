<?php

namespace App\Domains\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NotificationDelivery extends Model
{
    public const CHANNEL_IN_APP = 'IN_APP';
    public const CHANNEL_PUSH = 'PUSH';
    public const CHANNEL_EMAIL = 'EMAIL';
    public const CHANNEL_WEB = 'WEB';

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_SENT = 'SENT';
    public const STATUS_FAILED = 'FAILED';

    public const PROVIDER_REVERB = 'REVERB';

    protected $table = 'notification_deliveries';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'notification_inbox_id',
        'channel',
        'status',
        'provider',
        'provider_message_id',
        'attempted_at',
        'delivered_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'notification_inbox_id' => 'integer',
        'attempted_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(NotificationInbox::class, 'notification_inbox_id');
    }
}
