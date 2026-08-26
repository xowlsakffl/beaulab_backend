<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Dto\Hospital;

use App\Domains\AccountHospital\Models\HospitalAccountInvitation;

final readonly class HospitalAccountInvitationForHospitalDto
{
    public static function fromModel(HospitalAccountInvitation $invitation): array
    {
        $hospitalName = $invitation->source_type === HospitalAccountInvitation::SOURCE_HOSPITAL
            ? $invitation->hospital?->name
            : $invitation->hospitalEntry?->hospital_name;

        return [
            'hospital_name' => (string) $hospitalName,
            'expires_at' => $invitation->expires_at?->toISOString(),
            'phone_verification_required' => true,
        ];
    }
}
