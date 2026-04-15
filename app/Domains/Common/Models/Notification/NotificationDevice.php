<?php

namespace App\Domains\Common\Models\Notification;

use Illuminate\Database\Eloquent\Model;

/**
 * 푸시/웹 알림 디바이스 토큰 모델.
 * 긴 토큰 원문은 text로 저장하고 중복 제약은 push_token_hash로 처리한다.
 */
final class NotificationDevice extends Model
{
    public const PLATFORM_IOS = 'IOS';

    public const PLATFORM_ANDROID = 'ANDROID';

    public const PLATFORM_WEB = 'WEB';

    protected $table = 'notification_devices';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'owner_type',
        'owner_id',
        'platform',
        'device_uuid',
        'push_token',
        'push_token_hash',
        'app_version',
        'last_seen_at',
        'revoked_at',
        'metadata',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
