<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;

final class HospitalEventApplicationGuard
{
    // 신청 저장 트랜잭션 안에서 이벤트와 병의원 상태를 함께 확정한다.
    public function lockOpenEvent(int $eventId): HospitalEvent
    {
        $event = HospitalEvent::query()->whereKey($eventId)->lockForUpdate()->first();
        $hospital = $event === null ? null : Hospital::query()
            ->whereKey($event->hospital_id)->lockForUpdate()->first();

        if ($event === null || ! $event->isApplicationOpen()
            || $hospital === null || $hospital->status !== Hospital::STATUS_ACTIVE
            || $hospital->allow_status !== Hospital::ALLOW_APPROVED) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '신청 가능한 이벤트가 아닙니다.');
        }

        return $event->setRelation('hospital', $hospital);
    }
}
