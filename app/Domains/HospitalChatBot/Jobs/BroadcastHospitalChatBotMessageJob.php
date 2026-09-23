<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Jobs;

use App\Domains\HospitalChatBot\Events\HospitalChatBotConsultationMessageCreated;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * 저장이 커밋된 뒤에 메시지를 발행한다.
 *
 * broadcast_pending을 끄는 것과 발행을 한 트랜잭션에서 처리하므로,
 * 중간에 실패하면 표식이 남아 hospital-chatbot:dispatch-pending-broadcasts가 다시 집어 간다.
 */
final class BroadcastHospitalChatBotMessageJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly int $messageId)
    {
        $this->onConnection('redis')->onQueue('hospital-chatbot')->afterCommit();
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $message = HospitalChatBotConsultationMessage::query()
                ->whereKey($this->messageId)
                ->lockForUpdate()
                ->first();

            if (! $message || ! $message->broadcast_pending) {
                return;
            }

            HospitalChatBotConsultationMessageCreated::dispatch(
                (int) $message->id,
                (int) $message->consultation_id,
            );

            $message->forceFill(['broadcast_pending' => false])->save();
        });
    }
}
