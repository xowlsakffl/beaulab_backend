<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Jobs;

use App\Domains\Common\Sms\Actions\SendSmsDeliveryAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class SendSmsDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(
        public readonly int $deliveryId,
    ) {
        $this->onConnection('redis');
        $this->onQueue((string) config('sms.queue', 'sms'));
        $this->afterCommit();
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function handle(SendSmsDeliveryAction $action): void
    {
        $action->execute($this->deliveryId);
    }

    public function failed(Throwable $exception): void
    {
        app(SendSmsDeliveryAction::class)->markFailed($this->deliveryId, $exception);
    }
}
