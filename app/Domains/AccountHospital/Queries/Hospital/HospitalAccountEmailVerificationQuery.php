<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Queries\Hospital;

use App\Domains\AccountHospital\Models\HospitalAccountEmailVerification;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationGuard;
use App\Domains\AccountHospital\Support\HospitalAccountInvitationToken;
use Illuminate\Database\Eloquent\Builder;

final class HospitalAccountEmailVerificationQuery
{
    public function lockInvitation(string $token): HospitalAccountInvitation
    {
        return HospitalAccountInvitationGuard::assertActive(
            HospitalAccountInvitation::query()->where('token_hash', HospitalAccountInvitationToken::hash($token))
                ->lockForUpdate()->first()
        );
    }

    private function forInvitation(HospitalAccountInvitation $invitation): Builder
    {
        return HospitalAccountEmailVerification::query()->where(
            'hospital_account_invitation_id',
            $invitation->getKey(),
        );
    }

    public function latestForUpdate(HospitalAccountInvitation $invitation): ?HospitalAccountEmailVerification
    {
        return $this->forInvitation($invitation)->latest('id')->lockForUpdate()->first();
    }

    public function invalidatePending(HospitalAccountInvitation $invitation): void
    {
        $this->forInvitation($invitation)->whereNull('consumed_at')->whereNull('invalidated_at')
            ->update(['invalidated_at' => now(), 'updated_at' => now()]);
    }

    public function create(array $attributes): HospitalAccountEmailVerification
    {
        return HospitalAccountEmailVerification::query()->create($attributes);
    }

    public function lockVerification(HospitalAccountInvitation $invitation, int $id): ?HospitalAccountEmailVerification
    {
        return $this->forInvitation($invitation)->whereKey($id)->lockForUpdate()->first();
    }
}
