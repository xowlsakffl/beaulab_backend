<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Dto\Staff\HospitalStatusChangeRequestForStaffDto;
use App\Domains\Hospital\Jobs\HospitalStatusChangeRequestNotificationJob;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use App\Domains\Hospital\Queries\Staff\HospitalStatusChangeRequestForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalStatusChangeRequestCreateForStaffAction
{
    public function __construct(
        private readonly HospitalStatusChangeRequestForStaffQuery $query,
    ) {}

    /** @return array{request: array, message: string} */
    public function execute(Hospital $hospital, array $payload): array
    {
        Gate::authorize('requestStatusChange', $hospital);

        $actor = auth()->user();
        if (! $actor instanceof AccountStaff) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        $request = DB::transaction(function () use ($hospital, $payload, $actor): HospitalStatusChangeRequest {
            $lockedHospital = $this->query->hospitalForUpdate((int) $hospital->getKey());
            if (! $lockedHospital) {
                throw new CustomException(ErrorCode::NOT_FOUND, '병의원을 찾을 수 없습니다.');
            }

            $targetStatus = (string) $payload['target_status'];
            if ($lockedHospital->status === Hospital::STATUS_WITHDRAWN) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '탈퇴한 병의원은 운영상태를 변경할 수 없습니다.');
            }

            if ($lockedHospital->status === $targetStatus) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 요청한 운영상태로 적용되어 있습니다.');
            }

            if ($this->query->pendingForHospital((int) $lockedHospital->getKey())) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 처리 대기 중인 운영상태 변경 신청이 있습니다.');
            }

            return $this->query->loadDetail($this->query->create([
                'hospital_id' => (int) $lockedHospital->getKey(),
                'previous_status' => (string) $lockedHospital->status,
                'target_status' => $targetStatus,
                'status' => HospitalStatusChangeRequest::STATUS_PENDING,
                'reason' => trim((string) $payload['reason']),
                'requested_by_staff_id' => (int) $actor->getKey(),
            ]));
        });

        HospitalStatusChangeRequestNotificationJob::dispatch(
            (int) $request->getKey(),
            HospitalStatusChangeRequestNotificationJob::EVENT_REQUESTED,
        )->afterCommit();

        return [
            'request' => HospitalStatusChangeRequestForStaffDto::fromModel($request),
            'message' => '운영중지 신청이 접수되었습니다.',
        ];
    }
}
