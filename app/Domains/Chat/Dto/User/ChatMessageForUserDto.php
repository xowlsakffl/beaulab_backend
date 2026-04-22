<?php

namespace App\Domains\Chat\Dto\User;

use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * ChatMessageForUserDto DTO.
 */
final readonly class ChatMessageForUserDto
{
    public function __construct(
        public int $id,
        public int $chatId,
        public int $senderUserId,
        public bool $isMine,
        public string $messageType,
        public ?string $body,
        public ?int $replyToMessageId,
        public mixed $metadata,
        public array $attachments,
        public ?array $sender,
        public ?string $editedAt,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(ChatMessage $message, int $currentUserId): self
    {
        return new self(
            id: (int) $message->id,
            chatId: (int) $message->chat_id,
            senderUserId: (int) $message->sender_user_id,
            isMine: (int) $message->sender_user_id === $currentUserId,
            messageType: (string) $message->message_type,
            body: $message->body,
            replyToMessageId: $message->reply_to_message_id ? (int) $message->reply_to_message_id : null,
            metadata: $message->metadata,
            attachments: self::attachments($message)
                ->map(static fn (Media $media): array => self::media($media))
                ->values()
                ->all(),
            sender: self::sender($message),
            editedAt: $message->edited_at?->toISOString(),
            createdAt: $message->created_at?->toISOString(),
            updatedAt: $message->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'chat_id' => $this->chatId,
            'sender_user_id' => $this->senderUserId,
            'is_mine' => $this->isMine,
            'message_type' => $this->messageType,
            'body' => $this->body,
            'reply_to_message_id' => $this->replyToMessageId,
            'metadata' => $this->metadata,
            'attachments' => $this->attachments,
            'sender' => $this->sender,
            'edited_at' => $this->editedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
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

    private static function sender(ChatMessage $message): ?array
    {
        if (! $message->relationLoaded('sender') || ! $message->sender) {
            return null;
        }

        return [
            'id' => (int) $message->sender->id,
            'nickname' => (string) $message->sender->nickname,
            'email' => (string) $message->sender->email,
        ];
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
