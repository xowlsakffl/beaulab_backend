<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Queries\Hospital;

use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Models\HospitalAccountPhoneVerification;

final class HospitalAccountPhoneVerificationQuery
{
    public function lockInvitationByTokenHash(string $tokenHash): ?HospitalAccountInvitation
    {
        return HospitalAccountInvitation::query()
            ->with(['hospital:id,name', 'hospitalEntry:id,hospital_name'])
            ->where('token_hash', $tokenHash)
            ->lockForUpdate()
            ->first();
    }

    public function latestForUpdate(int $invitationId, string $phone): ?HospitalAccountPhoneVerification
    {
        return HospitalAccountPhoneVerification::query()
            ->where('hospital_account_invitation_id', $invitationId)
            ->where('phone', $phone)
            ->latest('id')
            ->lockForUpdate()
            ->first();
    }

    public function invalidatePending(int $invitationId): void
    {
        HospitalAccountPhoneVerification::query()
            ->where('hospital_account_invitation_id', $invitationId)
            ->whereNull('consumed_at')
            ->whereNull('invalidated_at')
            ->update([
                'invalidated_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function create(array $attributes): HospitalAccountPhoneVerification
    {
        return HospitalAccountPhoneVerification::query()->create($attributes);
    }

    public function lockVerification(
        int $invitationId,
        int $verificationId,
    ): ?HospitalAccountPhoneVerification {
        return HospitalAccountPhoneVerification::query()
            ->whereKey($verificationId)
            ->where('hospital_account_invitation_id', $invitationId)
            ->lockForUpdate()
            ->first();
    }
}
