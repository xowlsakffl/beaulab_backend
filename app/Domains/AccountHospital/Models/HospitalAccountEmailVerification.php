<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalAccountEmailVerification extends Model
{
    protected $table = 'hospital_account_email_verifications';

    protected $fillable = [
        'hospital_account_invitation_id',
        'email',
        'code_hash',
        'verification_token_hash',
        'failed_attempt_count',
        'code_expires_at',
        'verified_at',
        'verification_expires_at',
        'consumed_at',
        'invalidated_at',
    ];

    protected $hidden = [
        'code_hash',
        'verification_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'failed_attempt_count' => 'integer',
            'code_expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'verification_expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'invalidated_at' => 'datetime',
        ];
    }

    public function isCodeUsable(): bool
    {
        return $this->invalidated_at === null
            && $this->consumed_at === null
            && $this->verified_at === null
            && $this->code_expires_at?->isFuture() === true;
    }

    public function isVerificationUsable(): bool
    {
        return $this->invalidated_at === null
            && $this->consumed_at === null
            && $this->verified_at !== null
            && $this->verification_expires_at?->isFuture() === true;
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(HospitalAccountInvitation::class, 'hospital_account_invitation_id');
    }
}
