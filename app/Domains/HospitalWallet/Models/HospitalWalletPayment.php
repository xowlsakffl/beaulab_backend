<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalWalletPayment extends Model
{
    public const string METHOD_VIRTUAL_ACCOUNT = 'VIRTUAL_ACCOUNT';

    public const string METHOD_MANUAL = 'MANUAL';

    protected $table = 'hospital_wallet_payments';

    protected $fillable = [
        'hospital_wallet_operation_id',
        'payment_method',
        'provider',
        'provider_transaction_id',
        'depositor_name',
        'supply_amount',
        'vat_amount',
        'payment_amount',
        'virtual_account_bank',
        'virtual_account_number',
        'virtual_account_expires_at',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'supply_amount' => 'integer',
        'vat_amount' => 'integer',
        'payment_amount' => 'integer',
        'virtual_account_expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(HospitalWalletOperation::class, 'hospital_wallet_operation_id');
    }
}
