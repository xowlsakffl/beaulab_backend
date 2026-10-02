<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Jobs;

use App\Domains\HospitalChatBot\Events\HospitalChatBotConsultationChanged;
use App\Domains\HospitalChatBot\Events\HospitalChatBotQueueChanged;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

final class RetryHospitalChatBotStateBroadcastJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public readonly int $consultationId)
    {
        $this->onConnection('redis')->onQueue('hospital-chatbot')->afterCommit();
    }

    public function backoff(): array
    {
        return [5, 15, 30, 60];
    }

    public function handle(): void
    {
        // 재시도 동안 상담 상태가 바뀌었으면 오래된 상태를 발행하지 않는다.
        $consultation = HospitalChatBotConsultation::query()->find($this->consultationId);
        if (! $consultation) {
            return;
        }
        HospitalChatBotConsultationChanged::dispatch($this->consultationId, (string) $consultation->status);
        HospitalChatBotQueueChanged::dispatch($this->consultationId, (string) $consultation->status);
    }
}
