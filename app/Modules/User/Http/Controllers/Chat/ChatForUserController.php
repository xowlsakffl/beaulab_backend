<?php

namespace App\Modules\User\Http\Controllers\Chat;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Actions\User\ChatDeleteForUserAction;
use App\Domains\Chat\Actions\User\ChatListForUserAction;
use App\Domains\Chat\Actions\User\ChatMessageListForUserAction;
use App\Domains\Chat\Actions\User\ChatMessageSendForUserAction;
use App\Domains\Chat\Actions\User\ChatNotificationUpdateForUserAction;
use App\Domains\Chat\Actions\User\ChatReadForUserAction;
use App\Domains\Chat\Models\Chat;
use App\Modules\User\Http\Requests\Chat\ChatFirstMessageSendForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatListForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatMessageListForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatMessageSendForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatNotificationUpdateForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatReadForUserRequest;

/**
 * 앱 사용자 채팅 API 컨트롤러.
 * auth:sanctum 사용자 검증 후 채팅 도메인 Action으로 유스케이스를 위임한다.
 */
final class ChatForUserController extends Controller
{
    public function getChatsForUser(ChatListForUserRequest $request, ChatListForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($user, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getMessagesForUser(
        Chat $chat,
        ChatMessageListForUserRequest $request,
        ChatMessageListForUserAction $action,
    ) {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($chat, $user, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function sendMessageForUser(
        Chat $chat,
        ChatMessageSendForUserRequest $request,
        ChatMessageSendForUserAction $action,
    ) {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($chat, $user, $request->validated());

        return ApiResponse::success($result['message'] ?? $result);
    }

    public function sendFirstMessageForUser(
        ChatFirstMessageSendForUserRequest $request,
        ChatMessageSendForUserAction $action,
    ) {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->executeFirst(
            $user,
            (int) $request->validated('peer_user_id'),
            $request->validated(),
        );

        return ApiResponse::success($result['message'] ?? $result);
    }

    public function readChatForUser(Chat $chat, ChatReadForUserRequest $request, ChatReadForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($chat, $user, $request->validated());

        return ApiResponse::success($result['chat'] ?? $result);
    }

    public function updateNotificationForUser(
        Chat $chat,
        ChatNotificationUpdateForUserRequest $request,
        ChatNotificationUpdateForUserAction $action,
    ) {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute(
            $chat,
            $user,
            (bool) $request->validated('notifications_enabled'),
        );

        return ApiResponse::success($result['chat'] ?? $result);
    }

    public function deleteChatForUser(Chat $chat, ChatDeleteForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($chat, $user);

        return ApiResponse::success($result['chat'] ?? $result);
    }
}
