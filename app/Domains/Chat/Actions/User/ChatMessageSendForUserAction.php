<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatMessageForUserDto;
use App\Domains\Chat\Events\ChatMessageCreated;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Queries\User\ChatMessageSendForUserQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\Notification\Actions\CreateNotificationAction;
use App\Domains\Common\Notification\Models\NotificationDelivery;
use App\Domains\Common\Notification\Models\NotificationInbox;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * 앱 사용자 메시지 전송 유스케이스.
 * 기존 채팅방 전송과 첫 메시지 전송을 같은 흐름으로 처리하고, 첨부 저장과 알림 생성까지 마무리한다.
 */
final class ChatMessageSendForUserAction
{
    public function __construct(
        private readonly ChatMessageSendForUserQuery $query,
        private readonly CreateNotificationAction $createNotificationAction,
        private readonly MediaAttachDeleteAction $mediaAttachDeleteAction,
    ) {}

    public function execute(AccountUser $user, array $payload, ?Chat $chat = null): array
    {
        $result = $this->query->create($user, $payload, $chat);

        /** @var ChatMessage $message */
        $message = $result['message'];
        $attachmentsCreated = $this->createAttachments($message, $payload['attachments'] ?? []);

        if ((bool) $result['created'] || $attachmentsCreated) {
            $message->load(['sender:id,nickname,email', 'attachments']);

            ChatMessageCreated::dispatch(
                (int) $message->id,
                (int) $message->chat_id,
                (int) $message->sender_user_id,
            );

            $this->createPeerNotifications($message, $user);
        }

        return [
            'message' => ChatMessageForUserDto::fromModel($message, (int) $user->id)->toArray(),
        ];
    }

    private function createAttachments(ChatMessage $message, mixed $value): bool
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
