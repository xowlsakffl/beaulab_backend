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

final class HospitalWallet extends Model
{
    use HasFactory;

    protected $table = 'hospital_wallets';

    protected $fillable = [
        'hospital_id',
        'paid_balance',
        'reserved_paid_balance',
        'service_balance',
        'last_transaction_at',
    ];

    protected $casts = [
        'paid_balance' => 'integer',
        'reserved_paid_balance' => 'integer',
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

    public function operations(): HasMany
    {
        return $this->hasMany(HospitalWalletOperation::class, 'hospital_wallet_id')
            ->latest('id');
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

    public function availablePaidBalance(): int
    {
        return max(0, (int) $this->paid_balance - (int) $this->reserved_paid_balance);
    }

    public function availableTotalBalance(): int
    {
        return $this->availablePaidBalance() + (int) $this->service_balance;
    }
}
