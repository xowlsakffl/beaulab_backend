<?php

namespace App\Modules\User\Http\Controllers\Chat;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
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
        $result = $action->execute($this->user(), $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
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

    public function sendFirstMessageForUser(
        ChatFirstMessageSendForUserRequest $request,
        ChatMessageSendForUserAction $action,
    ) {
        $result = $action->executeFirst(
            $this->user(),
            (int) $request->validated('peer_user_id'),
            $request->validated(),
        );

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

    public function deleteChatForUser(Chat $chat, ChatDeleteForUserAction $action)
    {
        $result = $action->execute($chat, $this->user());

        return ApiResponse::success($result['chat'] ?? $result);
    }

    private function user(): AccountUser
    {
        $user = auth()->user();

        // User 모듈 API에서는 Sanctum 토큰 ability와 실제 모델 타입이 모두 맞아야 한다.
        if (! $user instanceof AccountUser) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        if (! $user->isActive()) {
            throw new CustomException(ErrorCode::FORBIDDEN, '활성 상태의 사용자만 채팅을 사용할 수 있습니다.');
        }

        return $user;
    }
}
