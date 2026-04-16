<?php

/**
 * 앱 사용자 API 라우트 파일.
 * 인증/권한 미들웨어와 컨트롤러 매핑만 두고 비즈니스 로직은 컨트롤러와 Action 계층으로 위임한다.
 */

use App\Modules\User\Http\Controllers\Auth\AuthForUserController;
use App\Modules\User\Http\Controllers\Block\AccountUserBlockForUserController;
use App\Modules\User\Http\Controllers\Chat\ChatForUserController;
use App\Modules\User\Http\Controllers\Notification\NotificationForUserController;
use App\Modules\User\Http\Controllers\Talk\TalkSaveForUserController;
use Illuminate\Support\Facades\Route;

// 앱 사용자 API

// 로그인은 토큰이 없는 상태에서 호출되므로 auth 미들웨어 밖에 둔다.
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForUserController::class, 'login'])
        ->name('login')
        ->middleware('throttle:6,1');
});

// 아래 API는 Sanctum actor:user 토큰만 허용한다.
Route::middleware(['auth:sanctum', 'abilities:actor:user'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForUserController::class, 'logout'])
            ->name('logout');
    });

    Route::get('/profile', [AuthForUserController::class, 'getMyProfile'])
        ->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForUserController::class, 'updateMyProfile'])
        ->name('profile.update');
    Route::match(['put', 'patch'], '/password', [AuthForUserController::class, 'updateMyPassword'])
        ->name('password.update')
        ->middleware('throttle:6,1');

    // 앱 사용자 1:1 채팅 API
    Route::get('chats', [ChatForUserController::class, 'getChatsForUser'])
        ->name('chats.getChatsForUser');
    Route::post('chats/messages', [ChatForUserController::class, 'sendFirstMessageForUser'])
        ->name('chats.sendFirstMessageForUser');
    Route::get('chats/{chat}/messages', [ChatForUserController::class, 'getMessagesForUser'])
        ->name('chats.getMessagesForUser');
    Route::post('chats/{chat}/messages', [ChatForUserController::class, 'sendMessageForUser'])
        ->name('chats.sendMessageForUser');
    Route::post('chats/{chat}/read', [ChatForUserController::class, 'readChatForUser'])
        ->name('chats.readChatForUser');
    Route::match(['put', 'patch'], 'chats/{chat}/notifications', [ChatForUserController::class, 'updateNotificationForUser'])
        ->name('chats.updateNotificationForUser');
    Route::delete('chats/{chat}', [ChatForUserController::class, 'deleteChatForUser'])
        ->name('chats.deleteChatForUser');

    // 앱 사용자 토크 저장 API. 저장 여부는 사용자별 유니크 관계로 관리하고 talks.save_count를 함께 갱신한다.
    Route::post('talks/{talk}/save', [TalkSaveForUserController::class, 'saveTalkForUser'])
        ->name('talks.saveTalkForUser');
    Route::delete('talks/{talk}/save', [TalkSaveForUserController::class, 'unsaveTalkForUser'])
        ->name('talks.unsaveTalkForUser');

    // 앱 사용자 차단 API. 차단은 방향성 있는 유저 관계로 저장하고, 메시지 발송 전 검증에 사용한다.
    Route::get('blocks', [AccountUserBlockForUserController::class, 'getBlocksForUser'])
        ->name('blocks.getBlocksForUser');
    Route::post('blocks', [AccountUserBlockForUserController::class, 'blockUserForUser'])
        ->name('blocks.blockUserForUser');
    Route::delete('blocks/{blockedUserId}', [AccountUserBlockForUserController::class, 'unblockUserForUser'])
        ->name('blocks.unblockUserForUser')
        ->whereNumber('blockedUserId');

    // 공통 알림 API. 채팅 외 도메인 알림도 같은 inbox 구조를 사용한다.
    Route::get('notifications', [NotificationForUserController::class, 'getNotificationsForUser'])
        ->name('notifications.getNotificationsForUser');
    Route::get('notifications/unread-count', [NotificationForUserController::class, 'getUnreadCountForUser'])
        ->name('notifications.getUnreadCountForUser');
    Route::post('notifications/read-all', [NotificationForUserController::class, 'readAllNotificationsForUser'])
        ->name('notifications.readAllNotificationsForUser');
    Route::post('notifications/devices', [NotificationForUserController::class, 'registerDeviceForUser'])
        ->name('notifications.registerDeviceForUser');
    Route::post('notifications/devices/revoke', [NotificationForUserController::class, 'revokeDeviceForUser'])
        ->name('notifications.revokeDeviceForUser');
    Route::get('notifications/preferences', [NotificationForUserController::class, 'getPreferencesForUser'])
        ->name('notifications.getPreferencesForUser');
    Route::match(['put', 'patch'], 'notifications/preferences', [NotificationForUserController::class, 'updatePreferenceForUser'])
        ->name('notifications.updatePreferenceForUser');
    Route::post('notifications/{notificationInbox}/read', [NotificationForUserController::class, 'readNotificationForUser'])
        ->name('notifications.readNotificationForUser');
});
