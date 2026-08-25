<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Dto\Staff;

use App\Domains\AccountHospital\Models\HospitalAccountInvitation;

final readonly class HospitalAccountInvitationForStaffDto
{
    public static function fromModel(HospitalAccountInvitation $invitation): array
    {
        $sourceId = $invitation->source_type === HospitalAccountInvitation::SOURCE_HOSPITAL
            ? $invitation->hospital_id
            : $invitation->hospital_entry_id;

        return [
            'id' => (int) $invitation->id,
            'source_type' => (string) $invitation->source_type,
            'source_id' => (int) $sourceId,
            'recipient_email' => (string) $invitation->recipient_email,
            'status' => [
                'code' => $invitation->status(),
                'label' => HospitalAccountInvitation::statusLabel($invitation->status()),
            ],
            'expires_at' => $invitation->expires_at?->toISOString(),
            'sent_at' => $invitation->sent_at?->toISOString(),
            'used_at' => $invitation->used_at?->toISOString(),
            'revoked_at' => $invitation->revoked_at?->toISOString(),
            'created_by_staff' => $invitation->relationLoaded('createdByStaff') && $invitation->createdByStaff
                ? [
                    'id' => (int) $invitation->createdByStaff->id,
                    'name' => (string) $invitation->createdByStaff->name,
                ]
                : null,
            'completed_account_hospital_id' => $invitation->completed_account_hospital_id !== null
                ? (int) $invitation->completed_account_hospital_id
                : null,
            'created_at' => $invitation->created_at?->toISOString(),
            'updated_at' => $invitation->updated_at?->toISOString(),
        ];
    }
}
