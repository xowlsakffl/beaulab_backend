<?php

namespace App\Modules\User\Http\Controllers\Chat;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Actions\User\ChatCloseForUserAction;
use App\Domains\Chat\Actions\User\ChatListForUserAction;
use App\Domains\Chat\Actions\User\ChatMessageListForUserAction;
use App\Domains\Chat\Actions\User\ChatMessageSendForUserAction;
use App\Domains\Chat\Actions\User\ChatNotificationUpdateForUserAction;
use App\Domains\Chat\Actions\User\ChatOpenOrCreateForUserAction;
use App\Domains\Chat\Actions\User\ChatReadForUserAction;
use App\Domains\Chat\Models\Chat;
use App\Modules\User\Http\Requests\Chat\ChatListForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatMessageListForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatMessageSendForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatNotificationUpdateForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatOpenForUserRequest;
use App\Modules\User\Http\Requests\Chat\ChatReadForUserRequest;

final class ChatForUserController extends Controller
{
    public function getChatsForUser(ChatListForUserRequest $request, ChatListForUserAction $action)
    {
        $result = $action->execute($this->user(), $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function openChatForUser(ChatOpenForUserRequest $request, ChatOpenOrCreateForUserAction $action)
    {
        $result = $action->execute($this->user(), (int) $request->validated('peer_user_id'));

        return ApiResponse::success($result['chat'] ?? $result);
    }

    public function getMessagesForUser(
        Chat $chat,
        ChatMessageListForUserRequest $request,
        ChatMessageListForUserAction $action,
    ) {
        $result = $action->execute($chat, $this->user(), $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function sendMessageForUser(
        Chat $chat,
        ChatMessageSendForUserRequest $request,
        ChatMessageSendForUserAction $action,
    ) {
        $result = $action->execute($chat, $this->user(), $request->validated());

        return ApiResponse::success($result['message'] ?? $result);
    }

    public function readChatForUser(Chat $chat, ChatReadForUserRequest $request, ChatReadForUserAction $action)
    {
        $result = $action->execute($chat, $this->user(), $request->validated());

        return ApiResponse::success($result['chat'] ?? $result);
    }

    public function updateNotificationForUser(
        Chat $chat,
        ChatNotificationUpdateForUserRequest $request,
        ChatNotificationUpdateForUserAction $action,
    ) {
        $result = $action->execute(
            $chat,
            $this->user(),
            (bool) $request->validated('notifications_enabled'),
        );

        return ApiResponse::success($result['chat'] ?? $result);
    }

    public function closeChatForUser(Chat $chat, ChatCloseForUserAction $action)
    {
        $result = $action->execute($chat, $this->user());

        return ApiResponse::success($result['chat'] ?? $result);
    }

    private function user(): AccountUser
    {
        $user = auth()->user();

        if (! $user instanceof AccountUser) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        if (! $user->isActive()) {
            throw new CustomException(ErrorCode::FORBIDDEN, '활성 상태의 사용자만 채팅을 사용할 수 있습니다.');
        }

        return $user;
    }
}
