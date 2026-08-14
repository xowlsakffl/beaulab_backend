<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Models;

use Database\Factories\HospitalWalletOperationFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class HospitalWalletOperation extends Model
{
    use HasFactory;

    public const string TYPE_CHARGE = 'CHARGE';

    public const string TYPE_USAGE = 'USAGE';

    public const string TYPE_REFUND = 'REFUND';

    public const string TYPE_SERVICE_GRANT = 'SERVICE_GRANT';

    public const string TYPE_SERVICE_RECLAIM = 'SERVICE_RECLAIM';

    public const string TYPE_REVERSAL = 'REVERSAL';

    public const string TYPE_GROUP_CHARGE = 'CHARGE';

    public const string TYPE_GROUP_USAGE = 'USAGE';

    public const string TYPE_GROUP_REFUND = 'REFUND';

    public const string TYPE_GROUP_SERVICE = 'SERVICE';

    public const string TYPE_GROUP_ALL = 'ALL';

    public const string STATUS_PENDING = 'PENDING';

    public const string STATUS_COMPLETED = 'COMPLETED';

    public const string STATUS_CANCELED = 'CANCELED';

    public const string STATUS_REJECTED = 'REJECTED';

    public const string STATUS_FAILED = 'FAILED';

    private const array TYPES = [
        self::TYPE_CHARGE,
        self::TYPE_USAGE,
        self::TYPE_REFUND,
        self::TYPE_SERVICE_GRANT,
        self::TYPE_SERVICE_RECLAIM,
        self::TYPE_REVERSAL,
    ];

    private const array TYPE_GROUPS = [
        self::TYPE_GROUP_CHARGE,
        self::TYPE_GROUP_USAGE,
        self::TYPE_GROUP_REFUND,
        self::TYPE_GROUP_SERVICE,
        self::TYPE_GROUP_ALL,
    ];

    private const array STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELED,
        self::STATUS_REJECTED,
        self::STATUS_FAILED,
    ];

    protected $table = 'hospital_wallet_operations';

    protected $fillable = [
        'hospital_wallet_id',
        'batch_uuid',
        'type',
        'status',
        'amount',
        'idempotency_key',
        'requester_type',
        'requester_id',
        'requester_kind',
        'processor_type',
        'processor_id',
        'processor_kind',
        'reference_type',
        'reference_id',
        'reference_label',
        'reason',
        'processed_at',
        'canceled_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'requester_id' => 'integer',
        'processor_id' => 'integer',
        'reference_id' => 'integer',
        'processed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function newFactory(): Factory
    {
        return HospitalWalletOperationFactory::new();
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(HospitalWallet::class, 'hospital_wallet_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(HospitalWalletTransaction::class, 'hospital_wallet_operation_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(HospitalWalletPayment::class, 'hospital_wallet_operation_id');
    }

    public function refund(): HasOne
    {
        return $this->hasOne(HospitalWalletRefund::class, 'hospital_wallet_operation_id');
    }

    public function requester(): MorphTo
    {
        return $this->morphTo('requester', 'requester_type', 'requester_id');
    }

    public function processor(): MorphTo
    {
        return $this->morphTo('processor', 'processor_type', 'processor_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public static function types(): array
    {
        return self::TYPES;
    }

    public static function typeGroups(): array
    {
        return self::TYPE_GROUPS;
    }

    public static function statuses(): array
    {
        return self::STATUSES;
    }

    public static function typesForGroup(?string $group): array
    {
        return match ($group) {
            self::TYPE_GROUP_CHARGE => [self::TYPE_CHARGE],
            self::TYPE_GROUP_USAGE => [self::TYPE_USAGE],
            self::TYPE_GROUP_REFUND => [self::TYPE_REFUND],
            self::TYPE_GROUP_SERVICE => [self::TYPE_SERVICE_GRANT, self::TYPE_SERVICE_RECLAIM],
            default => [],
        };
    }

    public static function typeGroupOptions(): array
    {
        return [
            ['value' => self::TYPE_GROUP_CHARGE, 'label' => '충전'],
            ['value' => self::TYPE_GROUP_USAGE, 'label' => '소진'],
            ['value' => self::TYPE_GROUP_REFUND, 'label' => '환불'],
            ['value' => self::TYPE_GROUP_SERVICE, 'label' => '서비스 적립/회수'],
            ['value' => self::TYPE_GROUP_ALL, 'label' => '전체'],
        ];
    }

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            self::TYPE_CHARGE => '입금충전',
            self::TYPE_USAGE => '사용',
            self::TYPE_REFUND => '환불',
            self::TYPE_SERVICE_GRANT => '서비스 적립',
            self::TYPE_SERVICE_RECLAIM => '서비스 회수',
            self::TYPE_REVERSAL => '거래 취소',
            default => $type ?: '-',
        };
    }

    public static function statusLabel(?string $type, ?string $status): string
    {
        return match ([$type, $status]) {
            [self::TYPE_CHARGE, self::STATUS_PENDING] => '입금대기',
            [self::TYPE_CHARGE, self::STATUS_COMPLETED] => '완료',
            [self::TYPE_CHARGE, self::STATUS_CANCELED] => '취소',
            [self::TYPE_REFUND, self::STATUS_PENDING] => '환불신청',
            [self::TYPE_REFUND, self::STATUS_COMPLETED] => '환불완료',
            [self::TYPE_REFUND, self::STATUS_REJECTED] => '환불반려',
            [self::TYPE_USAGE, self::STATUS_COMPLETED],
            [self::TYPE_SERVICE_GRANT, self::STATUS_COMPLETED],
            [self::TYPE_SERVICE_RECLAIM, self::STATUS_COMPLETED],
            [self::TYPE_REVERSAL, self::STATUS_COMPLETED] => '완료',
            default => match ($status) {
                self::STATUS_PENDING => '처리대기',
                self::STATUS_COMPLETED => '완료',
                self::STATUS_CANCELED => '취소',
                self::STATUS_REJECTED => '반려',
                self::STATUS_FAILED => '실패',
                default => $status ?: '-',
            },
        };
    }

    public static function chargeStatusOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING, 'label' => '입금대기'],
            ['value' => self::STATUS_COMPLETED, 'label' => '완료'],
            ['value' => self::STATUS_CANCELED, 'label' => '취소'],
        ];
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCredit(): bool
    {
        return in_array($this->type, [self::TYPE_CHARGE, self::TYPE_SERVICE_GRANT], true);
    }
}
