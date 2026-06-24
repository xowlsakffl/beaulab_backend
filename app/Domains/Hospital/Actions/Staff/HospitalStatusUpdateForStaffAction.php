<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

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
     * @param array{status:string} $payload
     * @return array{hospital: array}
     */
    public function execute(Hospital $hospital, array $payload): array
    {
        Gate::authorize('update', $hospital);

        $beforeHistory = $this->historyRecordAction->capture($hospital);

        $updated = DB::transaction(function () use ($hospital, $payload, $beforeHistory): Hospital {
            $updatedHospital = $this->query->update($hospital, $payload['status']);
            $this->historyRecordAction->recordUpdated($updatedHospital, $beforeHistory);

            return $updatedHospital->fresh();
        });

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $updated
                    ->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories', 'features', 'operationHistories.actor'])
                    ->loadNewEventDBCount()
            )->toArray(),
        ];
    }
}
