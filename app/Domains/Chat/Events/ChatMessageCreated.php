<?php

namespace App\Domains\Chat\Events;

use App\Domains\Chat\Queries\ChatMessageBroadcastPayloadQuery;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 새 채팅 메시지를 private chat 채널로 전달하는 Reverb 이벤트.
 * 채팅 체감 지연을 줄이기 위해 큐를 거치지 않고 요청 흐름에서 즉시 브로드캐스트한다.
 */
final class ChatMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

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
