<?php

namespace App\Modules\Staff\Http\Controllers\Notification;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Notification\Actions\StaffNotificationForStaffAction;
use App\Modules\Staff\Http\Requests\Notification\NotificationForStaffRequest;

final class NotificationForStaffController extends Controller
{
    public function index(NotificationForStaffRequest $request, StaffNotificationForStaffAction $action)
    {
        return ApiResponse::success($action->listing($request->user(), $request->validated()));
    }

    public function unreadCount(NotificationForStaffRequest $request, StaffNotificationForStaffAction $action)
    {
        return ApiResponse::success($action->unread($request->user(), $request->validated()));
    }

    public function read(NotificationForStaffRequest $request, int $notification, StaffNotificationForStaffAction $action)
    {
        $messageId = $request->validated('latest_message_id');

        return ApiResponse::success($action->read($request->user(), $notification, (int) $request->validated('expected_event_count'), $messageId === null ? null : (int) $messageId));
    }

    public function readAll(NotificationForStaffRequest $request, StaffNotificationForStaffAction $action)
    {
        return ApiResponse::success($action->readAll($request->user(), $request->validated('read_all_token')));
    }

    public function preferences(NotificationForStaffRequest $request, StaffNotificationForStaffAction $action)
    {
        return ApiResponse::success($action->preferences($request->user()));
    }

    public function updatePreferences(NotificationForStaffRequest $request, StaffNotificationForStaffAction $action)
    {
        return ApiResponse::success($action->updatePreferences($request->user(), $request->validated('preferences')));
    }
}
