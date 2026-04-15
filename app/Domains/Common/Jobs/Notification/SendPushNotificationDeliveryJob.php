<?php

namespace App\Domains\Common\Jobs\Notification;

use App\Domains\Common\Actions\Notification\SendPushNotificationDeliveryAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * PUSH delivery를 notifications 큐에서 외부 provider로 전송하는 Job.
 * API 응답 시간을 늘리지 않기 위해 실제 네트워크 호출은 큐 워커가 처리한다.
 */
final class SendPushNotificationDeliveryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 45;

    public function __construct(
        public readonly int $deliveryId,
    ) {
        $this->onConnection('redis');
        $this->onQueue((string) config('notification_push.queue', 'notifications'));
    }

    public function handle(SendPushNotificationDeliveryAction $action): void
    {
        $action->execute($this->deliveryId);
    }
}
