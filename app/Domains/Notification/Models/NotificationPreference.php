<?php

namespace App\Domains\Notification\Models;

use Illuminate\Database\Eloquent\Model;

final class NotificationPreference extends Model
{
    public const DEFAULT_EVENT_TYPES = [
        NotificationInbox::EVENT_CHAT_MESSAGE_CREATED,
    ];

    protected $table = 'notification_preferences';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_type',
        'owner_id',
        'event_type',
        'in_app',
        'push',
        'email',
        'metadata',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'in_app' => 'boolean',
        'push' => 'boolean',
        'email' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'in_app' => true,
        'push' => true,
        'email' => false,
    ];
}
