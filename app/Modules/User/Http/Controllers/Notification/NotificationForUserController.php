<?php

namespace App\Modules\User\Http\Controllers\Notification;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Notification\Actions\User\NotificationDeviceRegisterForUserAction;
use App\Domains\Common\Notification\Actions\User\NotificationDeviceRevokeForUserAction;
use App\Domains\Common\Notification\Actions\User\NotificationListForUserAction;
use App\Domains\Common\Notification\Actions\User\NotificationPreferenceListForUserAction;
use App\Domains\Common\Notification\Actions\User\NotificationPreferenceUpdateForUserAction;
use App\Domains\Common\Notification\Actions\User\NotificationReadAllForUserAction;
use App\Domains\Common\Notification\Actions\User\NotificationReadForUserAction;
use App\Domains\Common\Notification\Actions\User\NotificationUnreadCountForUserAction;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Modules\User\Http\Requests\Notification\NotificationDeviceRegisterForUserRequest;
use App\Modules\User\Http\Requests\Notification\NotificationDeviceRevokeForUserRequest;
use App\Modules\User\Http\Requests\Notification\NotificationListForUserRequest;
use App\Modules\User\Http\Requests\Notification\NotificationPreferenceUpdateForUserRequest;
use Illuminate\Http\Request;

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
        $result = $action->execute($request->user(), $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function getUnreadCountForUser(Request $request, NotificationUnreadCountForUserAction $action)
    {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function readNotificationForUser(Request $request, NotificationInbox $notificationInbox, NotificationReadForUserAction $action)
    {
        $result = $action->execute($notificationInbox, $request->user());

        return ApiResponse::success($result['notification'] ?? $result);
    }

    public function readAllNotificationsForUser(Request $request, NotificationReadAllForUserAction $action)
    {
        return ApiResponse::success($action->execute($request->user()));
    }

    public function registerDeviceForUser(
        NotificationDeviceRegisterForUserRequest $request,
        NotificationDeviceRegisterForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $request->validated());

        return ApiResponse::success($result['device'] ?? $result);
    }

    public function revokeDeviceForUser(
        NotificationDeviceRevokeForUserRequest $request,
        NotificationDeviceRevokeForUserAction $action,
    ) {
        return ApiResponse::success($action->execute($request->user(), (string) $request->validated('push_token')));
    }

    public function getPreferencesForUser(Request $request, NotificationPreferenceListForUserAction $action)
    {
        $result = $action->execute($request->user());

        return ApiResponse::success($result['items']);
    }

    public function updatePreferenceForUser(
        NotificationPreferenceUpdateForUserRequest $request,
        NotificationPreferenceUpdateForUserAction $action,
    ) {
        $result = $action->execute($request->user(), $request->validated());

        return ApiResponse::success($result['preference'] ?? $result);
    }
}
