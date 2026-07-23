<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalStatusUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalStatusUpdateForStaffQuery $query,
        private readonly HospitalUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    /**
     * @param  array{status:string, reason?:string|null}  $payload
     * @return array{hospital: array}
     */
    public function execute(Hospital $hospital, array $payload): array
    {
        Gate::authorize('update', $hospital);

        $beforeStatus = (string) $hospital->status;

        $updated = DB::transaction(function () use ($hospital, $payload, $beforeStatus): Hospital {
            $updatedHospital = $this->query->update($hospital, $payload['status']);
            $this->historyRecordAction->recordStatusUpdated(
                $updatedHospital,
                $beforeStatus,
                (string) $updatedHospital->status,
                $payload['reason'] ?? null,
            );

            return $updatedHospital->fresh();
        });

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL);

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $updated
                    ->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories', 'features', 'operationHistories.actor'])
                    ->loadNewEventDBCount()
            )->toArray(),
        ];
    }
}
