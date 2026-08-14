<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 수신자별 문자 내용 스냅샷과 Provider 발송 결과를 관리한다.
 */
final class SmsDelivery extends Model
{
    public const string MESSAGE_SMS = 'SMS';

    public const string MESSAGE_LMS = 'LMS';

    public const string STATUS_PENDING = 'PENDING';

    public const string STATUS_PROCESSING = 'PROCESSING';

    public const string STATUS_SENT = 'SENT';

    public const string STATUS_FAILED = 'FAILED';

    public const string STATUS_SKIPPED = 'SKIPPED';

    protected $table = 'sms_deliveries';

    protected $fillable = [
        'sms_batch_id',
        'deduplication_key',
        'reference_type',
        'reference_id',
        'reference_label',
        'recipient_type',
        'recipient_id',
        'recipient_kinds',
        'phone',
        'phone_normalized',
        'message_type',
        'message_body',
        'byte_length',
        'status',
        'provider',
        'provider_message_id',
        'attempt_count',
        'queued_at',
        'attempted_at',
        'sent_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'reference_id' => 'integer',
        'recipient_id' => 'integer',
        'recipient_kinds' => 'array',
        'byte_length' => 'integer',
        'attempt_count' => 'integer',
        'queued_at' => 'datetime',
        'attempted_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SmsBatch::class, 'sms_batch_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo('recipient', 'recipient_type', 'recipient_id');
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => '발송대기',
            self::STATUS_PROCESSING => '발송중',
            self::STATUS_SENT => '발송완료',
            self::STATUS_FAILED => '발송실패',
            self::STATUS_SKIPPED => '발송제외',
            default => $status ?: '-',
        };
    }
}
