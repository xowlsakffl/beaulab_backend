<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalAccountIdentityVerification extends Model
{
    protected $table = 'hospital_account_identity_verifications';

    protected $fillable = [
        'hospital_account_invitation_id',
        'provider',
        'provider_reference_hash',
        'token_hash',
        'verified_name',
        'verified_phone',
        'ci_hash',
        'verified_at',
        'expires_at',
        'consumed_at',
    ];

    protected $hidden = [
        'provider_reference_hash',
        'token_hash',
        'ci_hash',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function isUsable(): bool
    {
        return $this->consumed_at === null
            && $this->verified_at !== null
            && $this->expires_at?->isFuture() === true;
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(HospitalAccountInvitation::class, 'hospital_account_invitation_id');
    }
}
