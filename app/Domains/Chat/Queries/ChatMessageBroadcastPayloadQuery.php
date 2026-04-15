<?php

namespace App\Domains\Chat\Queries;

use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

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

        return [
            'id' => (int) $message->id,
            'chat_id' => (int) $message->chat_id,
            'sender_user_id' => (int) $message->sender_user_id,
            'message_type' => (string) $message->message_type,
            'body' => $message->body,
            'reply_to_message_id' => $message->reply_to_message_id ? (int) $message->reply_to_message_id : null,
            'metadata' => $message->metadata,
            'attachments' => $this->attachments($message)
                ->map(static fn (Media $media): array => self::media($media))
                ->values()
                ->all(),
            'sender' => $message->relationLoaded('sender') && $message->sender
                ? [
                    'id' => (int) $message->sender->id,
                    'nickname' => (string) $message->sender->nickname,
                    'email' => (string) $message->sender->email,
                ]
                : null,
            'edited_at' => $message->edited_at?->toISOString(),
            'created_at' => $message->created_at?->toISOString(),
            'updated_at' => $message->updated_at?->toISOString(),
        ];
    }

    /**
     * @return Collection<int, Media>
     */
    private function attachments(ChatMessage $message): Collection
    {
        if (! $message->relationLoaded('attachments')) {
            return collect();
        }

        return $message->attachments;
    }

    private static function media(Media $media): array
    {
        return [
            'id' => (int) $media->id,
            'collection' => (string) $media->collection,
            'disk' => (string) $media->disk,
            'path' => (string) $media->path,
            'url' => Storage::disk((string) $media->disk)->url((string) $media->path),
            'mime_type' => $media->mime_type,
            'size' => $media->size !== null ? (int) $media->size : null,
            'width' => $media->width !== null ? (int) $media->width : null,
            'height' => $media->height !== null ? (int) $media->height : null,
            'sort_order' => (int) $media->sort_order,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }
}
