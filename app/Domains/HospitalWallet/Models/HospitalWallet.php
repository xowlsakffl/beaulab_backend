<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Models;

use App\Domains\Hospital\Models\Hospital;
use Database\Factories\HospitalWalletFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HospitalWallet 역할 정의.
 * 병의원별 유상 충전 잔액과 서비스 잔액의 현재값을 관리한다.
 */
final class HospitalWallet extends Model
{
    use HasFactory;

    protected $table = 'hospital_wallets';

    protected $fillable = [
        'hospital_id',
        'paid_balance',
        'service_balance',
        'last_transaction_at',
    ];

    protected $casts = [
        'paid_balance' => 'integer',
        'service_balance' => 'integer',
        'last_transaction_at' => 'datetime',
    ];

    protected static function newFactory(): Factory
    {
        return HospitalWalletFactory::new();
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(HospitalWalletTransaction::class, 'hospital_wallet_id')
            ->latest('id');
    }

    public function totalBalance(): int
    {
        return (int) $this->paid_balance + (int) $this->service_balance;
    }
}
