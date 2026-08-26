<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalStatusChangeRequestForStaffQuery;
use App\Domains\Hospital\Queries\Staff\HospitalStatusUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalStatusUpdateForStaffQuery $query,
        private readonly HospitalStatusChangeRequestForStaffQuery $statusChangeRequestQuery,
        private readonly HospitalUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    /**
     * @param  array{status:string, reason?:string|null}  $payload
     * @return array{hospital: array}
     */
    public function execute(Hospital $hospital, array $payload): array
    {
        Gate::authorize('updateStatus', $hospital);

        $actor = auth()->user();
        if (! $actor instanceof AccountStaff) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        $updated = DB::transaction(function () use ($hospital, $payload, $actor): Hospital {
            $lockedHospital = $this->statusChangeRequestQuery->hospitalForUpdate((int) $hospital->getKey());
            if (! $lockedHospital) {
                throw new CustomException(ErrorCode::NOT_FOUND, '병의원을 찾을 수 없습니다.');
            }

            $beforeStatus = (string) $lockedHospital->status;
            $updatedHospital = $this->query->update($lockedHospital, $payload['status']);
            $this->historyRecordAction->recordStatusUpdated(
                $updatedHospital,
                $beforeStatus,
                (string) $updatedHospital->status,
                $payload['reason'] ?? null,
            );
            $this->statusChangeRequestQuery->cancelPendingForHospital(
                (int) $updatedHospital->getKey(),
                (int) $actor->getKey(),
                '최고관리자의 직접 상태 변경으로 자동 취소되었습니다.',
            );

            return $updatedHospital->fresh()->load('pendingStatusChangeRequest.requester');
        });

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL);

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $updated
                    ->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories', 'features', 'operationHistories.actor', 'pendingStatusChangeRequest.requester'])
                    ->loadNewEventDBCount()
            )->toArray(),
        ];
    }
}
