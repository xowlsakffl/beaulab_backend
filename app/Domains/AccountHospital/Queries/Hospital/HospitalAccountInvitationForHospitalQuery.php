<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Queries\Hospital;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Models\HospitalAccountPhoneVerification;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalBusinessRegistration;
use App\Domains\HospitalEntry\Models\HospitalEntry;

final class HospitalAccountInvitationForHospitalQuery
{
    public function findByTokenHash(string $tokenHash, bool $lockForUpdate = false): ?HospitalAccountInvitation
    {
        $query = HospitalAccountInvitation::query()
            ->with([
                'hospital:id,name',
                'hospitalEntry:id,hospital_name',
            ])
            ->where('token_hash', $tokenHash);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function lockPhoneVerification(
        HospitalAccountInvitation $invitation,
        string $tokenHash,
    ): ?HospitalAccountPhoneVerification {
        return HospitalAccountPhoneVerification::query()
            ->where('hospital_account_invitation_id', $invitation->getKey())
            ->where('verification_token_hash', $tokenHash)
            ->lockForUpdate()
            ->first();
    }

    public function lockHospital(int $hospitalId): Hospital
    {
        return Hospital::query()
            ->with('accountHospital')
            ->lockForUpdate()
            ->findOrFail($hospitalId);
    }

    public function lockHospitalEntry(int $hospitalEntryId): HospitalEntry
    {
        return HospitalEntry::query()
            ->with(['hospital', 'businessRegistrationFile'])
            ->lockForUpdate()
            ->findOrFail($hospitalEntryId);
    }

    public function hospitalNameExists(string $name): bool
    {
        return Hospital::query()->where('name', $name)->exists();
    }

    public function businessNumberExists(string $businessNumber): bool
    {
        return HospitalBusinessRegistration::query()
            ->where('business_number', $businessNumber)
            ->exists();
    }

    public function nicknameExists(string $nickname): bool
    {
        return AccountHospital::query()->where('nickname', $nickname)->exists();
    }

    public function createHospital(array $attributes): Hospital
    {
        return Hospital::query()->create($attributes);
    }

    public function createBusinessRegistration(array $attributes): HospitalBusinessRegistration
    {
        return HospitalBusinessRegistration::query()->create($attributes);
    }

    public function createAccountHospital(array $attributes): AccountHospital
    {
        return AccountHospital::query()->create($attributes);
    }

    public function updateReceptionPhone(Hospital $hospital, string $phone): void
    {
        $hospital->forceFill(['ad_reception_phone_1' => $phone])->save();
    }

    public function completeHospitalEntry(HospitalEntry $entry, Hospital $hospital): void
    {
        $entry->forceFill([
            'hospital_id' => $hospital->getKey(),
            'converted_at' => now(),
        ])->save();
    }

    public function consumePhoneVerification(HospitalAccountPhoneVerification $verification): void
    {
        $verification->forceFill(['consumed_at' => now()])->save();
    }

    public function completeInvitation(
        HospitalAccountInvitation $invitation,
        AccountHospital $accountHospital,
    ): void {
        $invitation->forceFill([
            'used_at' => now(),
            'completed_account_hospital_id' => $accountHospital->getKey(),
        ])->save();
    }

    public function transferBusinessRegistrationMedia(
        HospitalEntry $entry,
        HospitalBusinessRegistration $businessRegistration,
    ): void {
        Media::query()
            ->for($entry)
            ->collection('hospital_entry_business_registration_file')
            ->lockForUpdate()
            ->update([
                'model_type' => $businessRegistration::class,
                'model_id' => $businessRegistration->getKey(),
                'collection' => 'business_registration_file',
                'updated_at' => now(),
            ]);
    }
}
