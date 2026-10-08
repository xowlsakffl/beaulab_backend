<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Support;

use App\Domains\Common\Notification\Actions\StaffNotificationPublisher;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;

final class HospitalStatusChangeRequestNotificationPublisher
{
    public function __construct(private readonly StaffNotificationPublisher $publisher) {}

    public function requested(HospitalStatusChangeRequest $request): void
    {
        $this->publisher->record(
            eventType: NotificationInbox::EVENT_HOSPITAL_STATUS_CHANGE_REQUESTED,
            eventKey: 'hospital-status-change-request:'.$request->id.':requested',
            targetType: NotificationInbox::TARGET_HOSPITAL_STATUS_CHANGE_REQUEST,
            targetId: (int) $request->id,
            payload: ['hospital_id' => (int) $request->hospital_id],
            actorStaffId: (int) $request->requested_by_staff_id,
        );
    }

    public function processed(HospitalStatusChangeRequest $request): void
    {
        if ($request->isPending() || $request->processed_at === null) {
            return;
        }

        // 구형 Job 재시도에도 동일하도록 변경 가능한 이름·상태는 원본에서 조회한다.
        $this->publisher->record(
            eventType: NotificationInbox::EVENT_HOSPITAL_STATUS_CHANGE_PROCESSED,
            eventKey: 'hospital-status-change-request:'.$request->id.':processed',
            targetType: NotificationInbox::TARGET_HOSPITAL_STATUS_CHANGE_REQUEST,
            targetId: (int) $request->id,
            payload: ['hospital_id' => (int) $request->hospital_id],
            recipientId: (int) $request->requested_by_staff_id,
            actorStaffId: $request->processed_by_staff_id === null ? null : (int) $request->processed_by_staff_id,
        );
    }
}
