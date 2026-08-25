<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Queries\Hospital;

use App\Domains\AccountHospital\Models\HospitalAccountIdentityVerification;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;

final class HospitalAccountIdentityVerificationQuery
{
    public function lockInvitation(int $invitationId): ?HospitalAccountInvitation
    {
        return HospitalAccountInvitation::query()
            ->lockForUpdate()
            ->find($invitationId);
    }

    public function lockByProviderReference(string $provider, string $providerReferenceHash): ?HospitalAccountIdentityVerification
    {
        return HospitalAccountIdentityVerification::query()
            ->where('provider', $provider)
            ->where('provider_reference_hash', $providerReferenceHash)
            ->lockForUpdate()
            ->first();
    }

    public function create(array $attributes): HospitalAccountIdentityVerification
    {
        return HospitalAccountIdentityVerification::query()->create($attributes);
    }

    public function refreshProof(
        HospitalAccountIdentityVerification $verification,
        array $attributes,
    ): HospitalAccountIdentityVerification {
        $verification->forceFill($attributes)->save();

        return $verification->refresh();
    }
}
