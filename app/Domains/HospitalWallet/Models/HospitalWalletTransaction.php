<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Models;

use Database\Factories\HospitalWalletTransactionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * HospitalWalletTransaction 역할 정의.
 * 병의원 충전금의 업무 단위 거래와 거래 시점 잔액 스냅샷을 관리한다.
 */
final class HospitalWalletTransaction extends Model
{
    use HasFactory;

    public const string TYPE_CHARGE = 'CHARGE';

    public const string TYPE_USAGE = 'USAGE';

    public const string TYPE_REFUND = 'REFUND';

    public const string TYPE_SERVICE_GRANT = 'SERVICE_GRANT';

    public const string TYPE_SERVICE_RECLAIM = 'SERVICE_RECLAIM';

    public const string TYPE_REVERSAL = 'REVERSAL';

    public const array TYPES = [
        self::TYPE_CHARGE,
        self::TYPE_USAGE,
        self::TYPE_REFUND,
        self::TYPE_SERVICE_GRANT,
        self::TYPE_SERVICE_RECLAIM,
        self::TYPE_REVERSAL,
    ];

    protected $table = 'hospital_wallet_transactions';

    protected $fillable = [
        'hospital_wallet_id',
        'type',
        'amount',
        'paid_balance_before',
        'paid_balance_after',
        'service_balance_before',
        'service_balance_after',
        'batch_uuid',
        'idempotency_key',
        'actor_type',
        'actor_id',
        'actor_kind',
        'reference_type',
        'reference_id',
        'reversed_transaction_id',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_balance_before' => 'integer',
        'paid_balance_after' => 'integer',
        'service_balance_before' => 'integer',
        'service_balance_after' => 'integer',
        'actor_id' => 'integer',
        'reference_id' => 'integer',
        'reversed_transaction_id' => 'integer',
        'metadata' => 'array',
    ];

    protected static function newFactory(): Factory
    {
        return HospitalWalletTransactionFactory::new();
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(HospitalWallet::class, 'hospital_wallet_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(HospitalWalletTransactionEntry::class, 'hospital_wallet_transaction_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo('actor', 'actor_type', 'actor_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_transaction_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_transaction_id');
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return self::TYPES;
    }

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            self::TYPE_CHARGE => '충전',
            self::TYPE_USAGE => '소진',
            self::TYPE_REFUND => '환불',
            self::TYPE_SERVICE_GRANT => '서비스 적립',
            self::TYPE_SERVICE_RECLAIM => '서비스 회수',
            self::TYPE_REVERSAL => '거래 취소',
            default => $type ?: '-',
        };
    }

    public function totalBalanceBefore(): int
    {
        return (int) $this->paid_balance_before + (int) $this->service_balance_before;
    }

    public function totalBalanceAfter(): int
    {
        return (int) $this->paid_balance_after + (int) $this->service_balance_after;
    }
}
