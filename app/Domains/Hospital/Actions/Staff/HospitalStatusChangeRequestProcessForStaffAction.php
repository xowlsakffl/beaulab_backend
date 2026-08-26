<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Hospital\Dto\Staff\HospitalStatusChangeRequestForStaffDto;
use App\Domains\Hospital\Jobs\HospitalStatusChangeRequestNotificationJob;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use App\Domains\Hospital\Queries\Staff\HospitalStatusChangeRequestForStaffQuery;
use App\Domains\Hospital\Queries\Staff\HospitalStatusUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalStatusChangeRequestProcessForStaffAction
{
    public function __construct(
        private readonly HospitalStatusChangeRequestForStaffQuery $requestQuery,
        private readonly HospitalStatusUpdateForStaffQuery $statusQuery,
        private readonly HospitalUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    /** @return array{request: array, message: string} */
    public function execute(HospitalStatusChangeRequest $statusChangeRequest, array $payload): array
    {
        Gate::authorize('processStatusChange', Hospital::class);

        $actor = auth()->user();
        if (! $actor instanceof AccountStaff) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        $processed = DB::transaction(function () use ($statusChangeRequest, $payload, $actor): HospitalStatusChangeRequest {
            $hospital = $this->requestQuery->hospitalForUpdate((int) $statusChangeRequest->hospital_id);
            $request = $this->requestQuery->requestForUpdate((int) $statusChangeRequest->getKey());

            if (! $hospital || ! $request) {
                throw new CustomException(ErrorCode::NOT_FOUND, '운영상태 변경 신청을 찾을 수 없습니다.');
            }

            if (! $request->isPending()) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 처리된 운영상태 변경 신청입니다.');
            }

            if ((int) $request->requested_by_staff_id === (int) $actor->getKey()) {
                throw new CustomException(ErrorCode::FORBIDDEN, '본인이 신청한 건은 직접 결재할 수 없습니다.');
            }

            $decision = (string) $payload['status'];
            if ($decision === HospitalStatusChangeRequest::STATUS_APPROVED) {
                if ((string) $hospital->status !== (string) $request->previous_status) {
                    throw new CustomException(
                        ErrorCode::INVALID_REQUEST,
                        '신청 이후 병의원 상태가 변경되어 승인할 수 없습니다.',
                    );
                }

                $beforeStatus = (string) $hospital->status;
                $updatedHospital = $this->statusQuery->update($hospital, (string) $request->target_status);
                $this->historyRecordAction->recordStatusUpdated(
                    $updatedHospital,
                    $beforeStatus,
                    (string) $updatedHospital->status,
                    (string) $request->reason,
                    'staff.hospital.status-request.approved',
                );
            }

            $request->forceFill([
                'status' => $decision,
                'decision_reason' => $decision === HospitalStatusChangeRequest::STATUS_REJECTED
                    ? trim((string) $payload['rejection_reason'])
                    : null,
                'processed_by_staff_id' => (int) $actor->getKey(),
                'processed_at' => now(),
            ])->save();

            return $this->requestQuery->loadDetail($request->refresh());
        });

        if ($processed->status === HospitalStatusChangeRequest::STATUS_APPROVED) {
            StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL);
        }

        HospitalStatusChangeRequestNotificationJob::dispatch(
            (int) $processed->getKey(),
            HospitalStatusChangeRequestNotificationJob::EVENT_PROCESSED,
        )->afterCommit();

        return [
            'request' => HospitalStatusChangeRequestForStaffDto::fromModel($processed),
            'message' => $processed->status === HospitalStatusChangeRequest::STATUS_APPROVED
                ? '운영중지 신청을 승인했습니다.'
                : '운영중지 신청을 반려했습니다.',
        ];
    }
}
