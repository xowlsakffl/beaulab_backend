<?php

namespace App\Domains\Chat\Events;

use App\Domains\Chat\Queries\ChatMessageBroadcastPayloadQuery;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 새 채팅 메시지를 private chat 채널로 전달하는 Reverb 이벤트.
 * 이벤트에는 ID만 싣고, 실제 payload 조회는 큐 처리 시 Query에서 수행한다.
 */
final class ChatMessageCreated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public string $connection = 'redis';

    public string $queue = 'chat';

    public function __construct(
        public readonly int $messageId,
        public readonly int $chatId,
        public readonly int $senderUserId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.{$this->chatId}")];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.created';
    }

    public function broadcastWith(): array
    {
        $message = app(ChatMessageBroadcastPayloadQuery::class)->payload($this->messageId);

        if ($message === null) {
            return [
                'id' => $this->messageId,
                'chat_id' => $this->chatId,
                'sender_user_id' => $this->senderUserId,
            ];
        }

        return [
            'message' => $message,
        ];
    }
}
