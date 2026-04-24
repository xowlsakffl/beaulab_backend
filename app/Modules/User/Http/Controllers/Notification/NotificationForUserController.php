<?php

namespace App\Modules\User\Http\Controllers\Notification;

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
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($user, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getUnreadCountForUser(NotificationUnreadCountForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        return ApiResponse::success($action->execute($user));
    }

    public function readNotificationForUser(NotificationInbox $notificationInbox, NotificationReadForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($notificationInbox, $user);

        return ApiResponse::success($result['notification'] ?? $result);
    }

    public function readAllNotificationsForUser(NotificationReadAllForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        return ApiResponse::success($action->execute($user));
    }

    public function registerDeviceForUser(
        NotificationDeviceRegisterForUserRequest $request,
        NotificationDeviceRegisterForUserAction $action,
    ) {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($user, $request->validated());

        return ApiResponse::success($result['device'] ?? $result);
    }

    public function revokeDeviceForUser(
        NotificationDeviceRevokeForUserRequest $request,
        NotificationDeviceRevokeForUserAction $action,
    ) {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        return ApiResponse::success($action->execute($user, (string) $request->validated('push_token')));
    }

    public function getPreferencesForUser(NotificationPreferenceListForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($user);

        return ApiResponse::success($result['items']);
    }

    public function updatePreferenceForUser(
        NotificationPreferenceUpdateForUserRequest $request,
        NotificationPreferenceUpdateForUserAction $action,
    ) {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->execute($user, $request->validated());

        return ApiResponse::success($result['preference'] ?? $result);
    }
}
