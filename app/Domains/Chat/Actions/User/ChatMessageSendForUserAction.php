<?php

namespace App\Domains\Chat\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatMessageForUserDto;
use App\Domains\Chat\Events\ChatMessageCreated;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Queries\User\ChatMessageSendForUserQuery;
use App\Domains\Chat\Support\ChatMatchKey;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Common\Actions\Notification\CreateNotificationAction;
use App\Domains\Common\Models\Notification\NotificationDelivery;
use App\Domains\Common\Models\Notification\NotificationInbox;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * 앱 사용자 메시지 발송 유스케이스.
 * 저장은 Query에 위임하고, 새 메시지일 때만 Reverb 브로드캐스트와 공통 알림 생성을 연결한다.
 */
final class ChatMessageSendForUserAction
{
    public function __construct(
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly MediaAttachDeleteAction $mediaAttachDeleteAction,
        private readonly ChatMessageSendForUserQuery $query,
    ) {}

    public function execute(Chat $chat, AccountUser $user, array $payload): array
    {
        $result = $this->query->store($chat, $user, $payload);

        /** @var ChatMessage $message */
        $message = $result['message'];
        $attachmentsStored = $this->storeAttachments($message, $payload['attachments'] ?? []);

        if ((bool) $result['created'] || $attachmentsStored) {
            $message->load(['sender:id,nickname,email', 'attachments']);

            ChatMessageCreated::dispatch(
                (int) $message->id,
                (int) $message->chat_id,
                (int) $message->sender_user_id,
            );

            $this->createPeerNotifications($message, $user);
        }

        return [
            'message' => ChatMessageForUserDto::fromModel($message, (int) $user->id),
        ];
    }

    public function executeFirst(AccountUser $user, int $peerUserId, array $payload): array
    {
        if ((int) $user->id === $peerUserId) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인과는 채팅방을 만들 수 없습니다.');
        }

        $peer = $this->query->findActivePeer($peerUserId);
        $matchKey = ChatMatchKey::forUsers((int) $user->id, $peerUserId);
        $result = $this->query->storeFirst($user, $peer, $matchKey, $payload);

        /** @var ChatMessage $message */
        $message = $result['message'];
        $attachmentsStored = $this->storeAttachments($message, $payload['attachments'] ?? []);

        if ((bool) $result['created'] || $attachmentsStored) {
            $message->load(['sender:id,nickname,email', 'attachments']);

            ChatMessageCreated::dispatch(
                (int) $message->id,
                (int) $message->chat_id,
                (int) $message->sender_user_id,
            );

            $this->createPeerNotifications($message, $user);
        }

        return [
            'message' => ChatMessageForUserDto::fromModel($message, (int) $user->id),
        ];
    }

    private function storeAttachments(ChatMessage $message, mixed $value): bool
    {
        $attachments = $this->attachments($value);

        if ($attachments === []) {
            return false;
        }

        $message->loadMissing('attachments');

        if ($message->attachments->isNotEmpty()) {
            return false;
        }

        try {
            $this->mediaAttachDeleteAction->attachMany(
                $message,
                $attachments,
                ChatMessage::MEDIA_COLLECTION_ATTACHMENTS,
                'chat/messages',
                'attachments',
                true,
            );
        } catch (Throwable $exception) {
            $this->mediaAttachDeleteAction->deleteCollectionMedia($message, ChatMessage::MEDIA_COLLECTION_ATTACHMENTS);

            throw $exception;
        }

        return true;
    }

    /**
     * @return list<UploadedFile>
     */
    private function attachments(mixed $value): array
    {
        if ($value instanceof UploadedFile) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $file): bool => $file instanceof UploadedFile
        ));
    }

    private function createPeerNotifications(ChatMessage $message, AccountUser $sender): void
    {
        $recipientIds = $this->query->notificationRecipientIds($message, $sender);

        // 현재 1:1 채팅이지만, 수신자 계산은 participant 기반으로 둬 확장 가능성을 남긴다.
        foreach ($recipientIds as $recipientId) {
            $this->createNotificationAction->execute([
                'recipient_type' => NotificationInbox::RECIPIENT_USER,
                'recipient_id' => (int) $recipientId,
                'actor_type' => NotificationInbox::ACTOR_USER,
                'actor_id' => (int) $sender->id,
                'event_type' => NotificationInbox::EVENT_CHAT_MESSAGE_CREATED,
                'title' => $this->normalizeNullableString($sender->nickname) ?? '새 메시지가 도착했습니다.',
                'body' => $this->notificationBody($message),
                'aggregation_key' => sprintf(
                    'recipient:user:%d:event:%s:target:chat:%d',
                    (int) $recipientId,
                    NotificationInbox::EVENT_CHAT_MESSAGE_CREATED,
                    (int) $message->chat_id,
                ),
                'target_type' => NotificationInbox::TARGET_CHAT,
                'target_id' => (int) $message->chat_id,
                'payload' => [
                    'chat_id' => (int) $message->chat_id,
                    'message_id' => (int) $message->id,
                    'sender_user_id' => (int) $sender->id,
                    'sender_user_nickname' => $this->normalizeNullableString($sender->nickname),
                ],
                'channels' => [
                    NotificationDelivery::CHANNEL_IN_APP,
                    NotificationDelivery::CHANNEL_PUSH,
                ],
            ]);
        }
    }

    private function notificationBody(ChatMessage $message): string
    {
        $body = $this->normalizeNullableString($message->body);

        if ($body !== null) {
            return mb_strimwidth($body, 0, 160, '...');
        }

        return match ($message->message_type) {
            ChatMessage::TYPE_IMAGE => '이미지를 보냈습니다.',
            ChatMessage::TYPE_FILE => '파일을 보냈습니다.',
            default => '새 메시지가 도착했습니다.',
        };
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
