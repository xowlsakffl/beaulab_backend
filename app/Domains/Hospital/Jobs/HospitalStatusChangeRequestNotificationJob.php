<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Jobs;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Actions\CreateNotificationAction;
use App\Domains\Common\Notification\Models\NotificationDelivery;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class HospitalStatusChangeRequestNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const EVENT_REQUESTED = 'REQUESTED';

    public const EVENT_PROCESSED = 'PROCESSED';

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly int $requestId,
        public readonly string $event,
    ) {
        $this->onConnection('redis');
        $this->onQueue('notifications');
    }

    public function handle(CreateNotificationAction $notificationAction): void
    {
        $request = HospitalStatusChangeRequest::query()
            ->with(['hospital:id,name', 'requester:id,name', 'processor:id,name'])
            ->find($this->requestId);

        if (! $request) {
            return;
        }

        if ($this->event === self::EVENT_REQUESTED) {
            $this->notifyReviewers($request, $notificationAction);

            return;
        }

        if ($this->event === self::EVENT_PROCESSED && $request->requester) {
            $this->createNotification(
                $notificationAction,
                $request,
                $request->requester,
                NotificationInbox::EVENT_HOSPITAL_STATUS_CHANGE_PROCESSED,
                '병의원 운영상태 변경 신청 처리',
                sprintf('%s 운영상태 변경 신청이 처리되었습니다.', $request->hospital?->name ?? '병의원'),
                $request->processor?->getKey(),
            );
        }
    }

    private function notifyReviewers(
        HospitalStatusChangeRequest $request,
        CreateNotificationAction $notificationAction,
    ): void {
        AccountStaff::query()
            ->permission(AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_PROCESS)
            ->where('status', AccountStaff::STATUS_ACTIVE)
            ->eachById(function (AccountStaff $reviewer) use ($request, $notificationAction): void {
                $this->createNotification(
                    $notificationAction,
                    $request,
                    $reviewer,
                    NotificationInbox::EVENT_HOSPITAL_STATUS_CHANGE_REQUESTED,
                    '병의원 운영중지 신청',
                    sprintf('%s 운영중지 신청이 접수되었습니다.', $request->hospital?->name ?? '병의원'),
                    $request->requester?->getKey(),
                );
            });
    }

    private function createNotification(
        CreateNotificationAction $notificationAction,
        HospitalStatusChangeRequest $request,
        AccountStaff $recipient,
        string $eventType,
        string $title,
        string $body,
        mixed $actorId,
    ): void {
        $notificationAction->execute([
            'recipient_type' => NotificationInbox::RECIPIENT_STAFF,
            'recipient_id' => (int) $recipient->getKey(),
            'actor_type' => NotificationInbox::ACTOR_STAFF,
            'actor_id' => $actorId ? (int) $actorId : null,
            'event_type' => $eventType,
            'title' => $title,
            'body' => $body,
            'target_type' => NotificationInbox::TARGET_HOSPITAL_STATUS_CHANGE_REQUEST,
            'target_id' => (int) $request->getKey(),
            'payload' => [
                'hospital_id' => (int) $request->hospital_id,
                'request_status' => (string) $request->status,
                'target_status' => (string) $request->target_status,
            ],
            'channels' => [NotificationDelivery::CHANNEL_IN_APP],
        ]);
    }
}
