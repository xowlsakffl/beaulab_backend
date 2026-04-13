<?php

namespace App\Domains\Chat\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 채팅방 읽음 상태 변경을 참여자에게 실시간 전달하는 Reverb 이벤트.
 * 수신 클라이언트는 reader_user_id가 본인이 아니면 상대방 읽음 상태로 반영한다.
 */
final class ChatReadStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public string $connection = 'redis';

    public string $queue = 'chat';

    public function __construct(
        public readonly int $chatId,
        public readonly int $readerUserId,
        public readonly ?int $lastReadMessageId,
        public readonly ?string $lastReadAt,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.{$this->chatId}")];
    }

    public function broadcastAs(): string
    {
        return 'chat.read.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
            'reader_user_id' => $this->readerUserId,
            'last_read_message_id' => $this->lastReadMessageId,
            'last_read_at' => $this->lastReadAt,
        ];
    }
}
