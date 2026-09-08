<?php

declare(strict_types=1);

namespace App\Domains\Chat\Jobs;

use App\Domains\Chat\Events\ChatMessageCreated;
use App\Domains\Chat\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

final class BroadcastChatMessageJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly int $messageId)
    {
        $this->onConnection('redis')->onQueue('chat')->afterCommit();
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $message = ChatMessage::query()->whereKey($this->messageId)->lockForUpdate()->first();
            if (! $message || ! $message->broadcast_pending) {
                return;
            }

            ChatMessageCreated::dispatch((int) $message->id, (int) $message->chat_id, (int) $message->sender_user_id);
            $message->forceFill(['broadcast_pending' => false])->save();
        });
    }
}
