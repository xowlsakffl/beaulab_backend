<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 도메인과 무관한 문자 발송 요청과 수신자별 처리 집계를 관리한다.
 */
final class SmsBatch extends Model
{
    public const string STATUS_PENDING = 'PENDING';

    public const string STATUS_PROCESSING = 'PROCESSING';

    public const string STATUS_SENT = 'SENT';

    public const string STATUS_PARTIAL_FAILED = 'PARTIAL_FAILED';

    public const string STATUS_FAILED = 'FAILED';

    public const array STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_SENT,
        self::STATUS_PARTIAL_FAILED,
        self::STATUS_FAILED,
    ];

    protected $table = 'sms_batches';

    protected $fillable = [
        'idempotency_key',
        'purpose',
        'request_hash',
        'message_template',
        'metadata',
        'target_count',
        'recipient_count',
        'sent_count',
        'failed_count',
        'skipped_count',
        'status',
        'actor_type',
        'actor_id',
        'queued_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'target_count' => 'integer',
        'recipient_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'skipped_count' => 'integer',
        'queued_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(SmsDelivery::class, 'sms_batch_id')
            ->orderBy('id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo('actor', 'actor_type', 'actor_id');
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return self::STATUSES;
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => '발송대기',
            self::STATUS_PROCESSING => '발송중',
            self::STATUS_SENT => '발송완료',
            self::STATUS_PARTIAL_FAILED => '일부실패',
            self::STATUS_FAILED => '발송실패',
            default => $status ?: '-',
        };
    }
}
