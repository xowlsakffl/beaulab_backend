<?php

use App\Modules\User\Http\Controllers\Auth\AuthForUserController;
use App\Modules\User\Http\Controllers\Chat\ChatForUserController;
use App\Modules\User\Http\Controllers\Notification\NotificationForUserController;
use Illuminate\Support\Facades\Route;

// 앱 사용자 API

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForUserController::class, 'login'])
        ->name('login')
        ->middleware('throttle:6,1');
});

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

    Route::get('chats', [ChatForUserController::class, 'getChatsForUser'])
        ->name('chats.getChatsForUser');
    Route::post('chats', [ChatForUserController::class, 'openChatForUser'])
        ->name('chats.openChatForUser');
    Route::get('chats/{chat}/messages', [ChatForUserController::class, 'getMessagesForUser'])
        ->name('chats.getMessagesForUser');
    Route::post('chats/{chat}/messages', [ChatForUserController::class, 'sendMessageForUser'])
        ->name('chats.sendMessageForUser');
    Route::post('chats/{chat}/read', [ChatForUserController::class, 'readChatForUser'])
        ->name('chats.readChatForUser');
    Route::match(['put', 'patch'], 'chats/{chat}/notifications', [ChatForUserController::class, 'updateNotificationForUser'])
        ->name('chats.updateNotificationForUser');
    Route::delete('chats/{chat}', [ChatForUserController::class, 'closeChatForUser'])
        ->name('chats.closeChatForUser');

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
