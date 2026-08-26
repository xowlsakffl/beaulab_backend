<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;

final class HospitalStatusChangeRequestForStaffQuery
{
    public function hospitalForUpdate(int $hospitalId): ?Hospital
    {
        return Hospital::query()->lockForUpdate()->find($hospitalId);
    }

    public function pendingForHospital(int $hospitalId): ?HospitalStatusChangeRequest
    {
        return HospitalStatusChangeRequest::query()
            ->where('hospital_id', $hospitalId)
            ->where('status', HospitalStatusChangeRequest::STATUS_PENDING)
            ->latest('id')
            ->lockForUpdate()
            ->first();
    }

    public function requestForUpdate(int $requestId): ?HospitalStatusChangeRequest
    {
        return HospitalStatusChangeRequest::query()->lockForUpdate()->find($requestId);
    }

    public function create(array $attributes): HospitalStatusChangeRequest
    {
        return HospitalStatusChangeRequest::query()->create($attributes);
    }

    public function loadDetail(HospitalStatusChangeRequest $request): HospitalStatusChangeRequest
    {
        return $request->load(['hospital:id,name,status', 'requester:id,name', 'processor:id,name']);
    }

    public function cancelPendingForHospital(int $hospitalId, int $processorId, string $reason): int
    {
        return HospitalStatusChangeRequest::query()
            ->where('hospital_id', $hospitalId)
            ->where('status', HospitalStatusChangeRequest::STATUS_PENDING)
            ->update([
                'status' => HospitalStatusChangeRequest::STATUS_CANCELLED,
                'decision_reason' => $reason,
                'processed_by_staff_id' => $processorId,
                'processed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
