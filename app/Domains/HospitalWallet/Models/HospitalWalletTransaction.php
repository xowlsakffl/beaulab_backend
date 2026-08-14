<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Models;

use App\Domains\HospitalWallet\Concerns\PreventsLedgerMutation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class HospitalWalletTransaction extends Model
{
    use PreventsLedgerMutation;

    protected $table = 'hospital_wallet_transactions';

    protected $fillable = [
        'hospital_wallet_operation_id',
        'hospital_wallet_id',
        'amount',
        'paid_balance_before',
        'paid_balance_after',
        'reserved_paid_balance_before',
        'reserved_paid_balance_after',
        'service_balance_before',
        'service_balance_after',
        'reversed_transaction_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_balance_before' => 'integer',
        'paid_balance_after' => 'integer',
        'reserved_paid_balance_before' => 'integer',
        'reserved_paid_balance_after' => 'integer',
        'service_balance_before' => 'integer',
        'service_balance_after' => 'integer',
        'reversed_transaction_id' => 'integer',
    ];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(HospitalWalletOperation::class, 'hospital_wallet_operation_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(HospitalWallet::class, 'hospital_wallet_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(HospitalWalletTransactionEntry::class, 'hospital_wallet_transaction_id')
            ->orderBy('id');
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_transaction_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_transaction_id');
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
