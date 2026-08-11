<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HospitalWalletTransactionEntry 역할 정의.
 * 충전금 거래 한 건에서 유상 또는 서비스 잔액의 실제 증감을 관리한다.
 */
final class HospitalWalletTransactionEntry extends Model
{
    public const string BALANCE_TYPE_PAID = 'PAID';

    public const string BALANCE_TYPE_SERVICE = 'SERVICE';

    public const array BALANCE_TYPES = [
        self::BALANCE_TYPE_PAID,
        self::BALANCE_TYPE_SERVICE,
    ];

    public const string DIRECTION_CREDIT = 'CREDIT';

    public const string DIRECTION_DEBIT = 'DEBIT';

    public const array DIRECTIONS = [
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

    /**
     * @return list<string>
     */
    public static function balanceTypes(): array
    {
        return self::BALANCE_TYPES;
    }

    /**
     * @return list<string>
     */
    public static function directions(): array
    {
        return self::DIRECTIONS;
    }
}
