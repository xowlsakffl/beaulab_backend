<?php

namespace App\Modules\User\Http\Controllers\Notification;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Actions\Notification\User\NotificationDeviceRegisterForUserAction;
use App\Domains\Common\Actions\Notification\User\NotificationDeviceRevokeForUserAction;
use App\Domains\Common\Actions\Notification\User\NotificationListForUserAction;
use App\Domains\Common\Actions\Notification\User\NotificationPreferenceListForUserAction;
use App\Domains\Common\Actions\Notification\User\NotificationPreferenceUpdateForUserAction;
use App\Domains\Common\Actions\Notification\User\NotificationReadAllForUserAction;
use App\Domains\Common\Actions\Notification\User\NotificationReadForUserAction;
use App\Domains\Common\Actions\Notification\User\NotificationUnreadCountForUserAction;
use App\Domains\Common\Models\Notification\NotificationInbox;
use App\Modules\User\Http\Requests\Notification\NotificationDeviceRegisterForUserRequest;
use App\Modules\User\Http\Requests\Notification\NotificationDeviceRevokeForUserRequest;
use App\Modules\User\Http\Requests\Notification\NotificationListForUserRequest;
use App\Modules\User\Http\Requests\Notification\NotificationPreferenceUpdateForUserRequest;

/**
 * 앱 사용자 알림 API 컨트롤러.
 * 인앱 알림 목록/읽음/푸시 디바이스/이벤트별 수신 설정을 Domain Action에 위임한다.
 */
final class NotificationForUserController extends Controller
{
    public function getNotificationsForUser(
        NotificationListForUserRequest $request,
        NotificationListForUserAction $action,
    ) {
        $result = $action->execute($this->user(), $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getUnreadCountForUser(NotificationUnreadCountForUserAction $action)
    {
        return ApiResponse::success($action->execute($this->user()));
    }

    public function readNotificationForUser(NotificationInbox $notificationInbox, NotificationReadForUserAction $action)
    {
        $result = $action->execute($notificationInbox, $this->user());

        return ApiResponse::success($result['notification'] ?? $result);
    }

    public function readAllNotificationsForUser(NotificationReadAllForUserAction $action)
    {
        return ApiResponse::success($action->execute($this->user()));
    }

    public function registerDeviceForUser(
        NotificationDeviceRegisterForUserRequest $request,
        NotificationDeviceRegisterForUserAction $action,
    ) {
        $result = $action->execute($this->user(), $request->validated());

        return ApiResponse::success($result['device'] ?? $result);
    }

    public function revokeDeviceForUser(
        NotificationDeviceRevokeForUserRequest $request,
        NotificationDeviceRevokeForUserAction $action,
    ) {
        return ApiResponse::success($action->execute($this->user(), (string) $request->validated('push_token')));
    }

    public function getPreferencesForUser(NotificationPreferenceListForUserAction $action)
    {
        $result = $action->execute($this->user());

        return ApiResponse::success($result['items']);
    }

    public function updatePreferenceForUser(
        NotificationPreferenceUpdateForUserRequest $request,
        NotificationPreferenceUpdateForUserAction $action,
    ) {
        $result = $action->execute($this->user(), $request->validated());

        return ApiResponse::success($result['preference'] ?? $result);
    }

    private function user(): AccountUser
    {
        $user = auth()->user();

        // 알림 API는 앱 사용자 계정만 접근 가능해야 다른 actor 토큰과 섞이지 않는다.
        if (! $user instanceof AccountUser) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        if (! $user->isActive()) {
            throw new CustomException(ErrorCode::FORBIDDEN, '활성 상태의 사용자만 알림을 사용할 수 있습니다.');
        }

        return $user;
    }
}
