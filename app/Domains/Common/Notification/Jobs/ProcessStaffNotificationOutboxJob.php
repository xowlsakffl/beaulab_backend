<?php

namespace App\Domains\Common\Notification\Jobs;

use App\Domains\Common\Notification\Actions\StaffNotificationOutboxAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

final class ProcessStaffNotificationOutboxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int $outboxId)
    {
        $this->onConnection('redis')->onQueue('staff-notifications');
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(StaffNotificationOutboxAction $action): void
    {
        $action->process($this->outboxId);
    }
}
