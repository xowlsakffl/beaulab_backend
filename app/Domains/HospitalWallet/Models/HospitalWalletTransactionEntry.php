<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Models;

use App\Domains\HospitalWallet\Concerns\PreventsLedgerMutation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalWalletTransactionEntry extends Model
{
    use PreventsLedgerMutation;

    public const string BALANCE_TYPE_ALL = 'ALL';

    public const string BALANCE_TYPE_PAID = 'PAID';

    public const string BALANCE_TYPE_SERVICE = 'SERVICE';

    public const string DIRECTION_CREDIT = 'CREDIT';

    public const string DIRECTION_DEBIT = 'DEBIT';

    private const array BALANCE_TYPES = [
        self::BALANCE_TYPE_PAID,
        self::BALANCE_TYPE_SERVICE,
    ];

    private const array DIRECTIONS = [
        self::DIRECTION_CREDIT,
        self::DIRECTION_DEBIT,
    ];

    protected $table = 'hospital_wallet_transaction_entries';

    protected $fillable = [
        'hospital_wallet_transaction_id',
        'balance_type',
        'direction',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(HospitalWalletTransaction::class, 'hospital_wallet_transaction_id');
    }

    public static function balanceTypes(): array
    {
        return self::BALANCE_TYPES;
    }

    public static function dashboardBalanceTypes(): array
    {
        return [self::BALANCE_TYPE_ALL, ...self::BALANCE_TYPES];
    }

    public static function directions(): array
    {
        return self::DIRECTIONS;
    }

    public static function balanceTypeLabel(?string $balanceType): string
    {
        return match ($balanceType) {
            self::BALANCE_TYPE_PAID => '유상 충전금',
            self::BALANCE_TYPE_SERVICE => '서비스 포인트',
            default => $balanceType ?: '-',
        };
    }

    public static function directionLabel(?string $direction): string
    {
        return match ($direction) {
            self::DIRECTION_CREDIT => '증가',
            self::DIRECTION_DEBIT => '감소',
            default => $direction ?: '-',
        };
    }
}
