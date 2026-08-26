<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Dto\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;

final readonly class HospitalStatusChangeRequestForStaffDto
{
    public static function fromModel(HospitalStatusChangeRequest $request): array
    {
        return [
            'id' => (int) $request->id,
            'hospital_id' => (int) $request->hospital_id,
            'hospital_name' => $request->hospital?->name,
            'previous_status' => [
                'code' => (string) $request->previous_status,
                'label' => Hospital::statusLabel((string) $request->previous_status),
            ],
            'target_status' => [
                'code' => (string) $request->target_status,
                'label' => Hospital::statusLabel((string) $request->target_status),
            ],
            'status' => (string) $request->status,
            'reason' => (string) $request->reason,
            'decision_reason' => $request->decision_reason,
            'requester' => $request->requester ? [
                'id' => (int) $request->requester->id,
                'name' => (string) $request->requester->name,
            ] : null,
            'processor' => $request->processor ? [
                'id' => (int) $request->processor->id,
                'name' => (string) $request->processor->name,
            ] : null,
            'processed_at' => $request->processed_at?->toISOString(),
            'created_at' => $request->created_at?->toISOString(),
            'updated_at' => $request->updated_at?->toISOString(),
        ];
    }
}
