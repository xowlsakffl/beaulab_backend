<?php

namespace App\Domains\Chat\Queries;

use App\Domains\Chat\Dto\ChatMessageBroadcastDto;
use App\Domains\Chat\Models\ChatMessage;

/**
 * Reverb 메시지 이벤트 payload 조회 전용 Query.
 * 브로드캐스트 payload는 모든 구독자에게 같으므로 is_mine 같은 사용자별 필드는 넣지 않는다.
 */
final class ChatMessageBroadcastPayloadQuery
{
    public function payload(int $messageId): ?array
    {
        $message = ChatMessage::query()
            ->with(['sender:id,nickname,email', 'attachments'])
            ->find($messageId);

        if (! $message instanceof ChatMessage) {
            return null;
        }

        return ChatMessageBroadcastDto::fromModel($message)->toArray();
    }
}
