<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Models;

use App\Domains\Common\Media\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

final class HospitalWalletRefund extends Model
{
    public const string DOCUMENT_BUSINESS_REGISTRATION = 'business-registration';

    public const string DOCUMENT_BANKBOOK = 'bankbook';

    public const string COLLECTION_BUSINESS_REGISTRATION_FILE = 'hospital_wallet_refund_business_registration_file';

    public const string COLLECTION_BANKBOOK_FILE = 'hospital_wallet_refund_bankbook_file';

    protected $table = 'hospital_wallet_refunds';

    protected $fillable = [
        'hospital_wallet_operation_id',
        'supply_amount',
        'vat_amount',
        'refund_amount',
        'bank_name',
        'account_number',
        'rejection_reason',
    ];

    protected $casts = [
        'supply_amount' => 'integer',
        'vat_amount' => 'integer',
        'refund_amount' => 'integer',
        'account_number' => 'encrypted',
    ];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(HospitalWalletOperation::class, 'hospital_wallet_operation_id');
    }

    public function businessRegistrationFile(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', self::COLLECTION_BUSINESS_REGISTRATION_FILE);
    }

    public function bankbookFile(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', self::COLLECTION_BANKBOOK_FILE);
    }
}
