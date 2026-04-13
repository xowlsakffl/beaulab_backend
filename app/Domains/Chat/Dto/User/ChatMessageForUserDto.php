<?php

namespace App\Domains\Chat\Dto\User;

use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * 앱 채팅 메시지 응답 DTO.
 * API 응답에서는 현재 사용자 기준 is_mine을 계산하지만, broadcast payload에는 이 값을 넣지 않는다.
 */
final readonly class ChatMessageForUserDto
{
    public static function fromModel(ChatMessage $message, int $currentUserId): array
    {
        return [
            'id' => (int) $message->id,
            'chat_id' => (int) $message->chat_id,
            'sender_user_id' => (int) $message->sender_user_id,
            'is_mine' => (int) $message->sender_user_id === $currentUserId,
            'message_type' => (string) $message->message_type,
            'body' => $message->body,
            'reply_to_message_id' => $message->reply_to_message_id ? (int) $message->reply_to_message_id : null,
            'metadata' => $message->metadata,
            'attachments' => self::attachments($message)
                ->map(static fn (Media $media): array => self::media($media))
                ->values()
                ->all(),
            'sender' => $message->relationLoaded('sender') && $message->sender
                ? [
                    'id' => (int) $message->sender->id,
                    'name' => (string) $message->sender->name,
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
    private static function attachments(ChatMessage $message): Collection
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
