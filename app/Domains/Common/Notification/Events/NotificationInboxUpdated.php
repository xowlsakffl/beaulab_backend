<?php

namespace App\Domains\Common\Notification\Events;

use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Common\Notification\Queries\NotificationInboxBroadcastPayloadQuery;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 사용자별 private 채널로 알림함 변경을 전달하는 Reverb 이벤트.
 * 현재는 USER 수신자만 broadcast 대상으로 허용한다.
 */
final class NotificationInboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $notificationId,
        public readonly string $recipientType,
        public readonly int $recipientId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->recipientId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.inbox.updated';
    }

    public function broadcastWith(): array
    {
        $notification = app(NotificationInboxBroadcastPayloadQuery::class)->payload($this->notificationId);

        if ($notification === null) {
            return [
                'id' => $this->notificationId,
                'recipient_type' => $this->recipientType,
                'recipient_id' => $this->recipientId,
            ];
        }

        return [
            'notification' => $notification,
        ];
    }

    public function broadcastWhen(): bool
    {
        return $this->recipientType === NotificationInbox::RECIPIENT_USER;
    }
}
