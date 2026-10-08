<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Jobs;

use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use App\Domains\Hospital\Support\HospitalStatusChangeRequestNotificationPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

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

    public function handle(HospitalStatusChangeRequestNotificationPublisher $publisher): void
    {
        if (! in_array($this->event, [self::EVENT_REQUESTED, self::EVENT_PROCESSED], true)) {
            return;
        }

        // 배포 전 notifications 큐에 남은 Job도 신규 Action과 같은 멱등 키를 사용한다.
        DB::transaction(function () use ($publisher): void {
            $request = HospitalStatusChangeRequest::query()->lockForUpdate()->find($this->requestId);
            if ($request === null) {
                return;
            }

            if ($this->event === self::EVENT_REQUESTED) {
                $publisher->requested($request);
            } else {
                $publisher->processed($request);
            }
        });
    }
}
