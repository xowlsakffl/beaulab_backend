<?php

namespace App\Modules\User\Http\Controllers\Block;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Actions\User\AccountUserBlockForUserAction;
use App\Domains\AccountUser\Models\AccountUser;
use App\Modules\User\Http\Requests\Block\AccountUserBlockCreateForUserRequest;
use App\Modules\User\Http\Requests\Block\AccountUserBlockListForUserRequest;

/**
 * 앱 사용자 차단 API 컨트롤러.
 * 차단은 방향성 있는 유저 관계로 처리하고, 상대에게 차단 여부를 직접 노출하지 않는다.
 */
final class AccountUserBlockForUserController extends Controller
{
    public function getBlocksForUser(
        AccountUserBlockListForUserRequest $request,
        AccountUserBlockForUserAction $action,
    ) {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->list($user, $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function blockUserForUser(
        AccountUserBlockCreateForUserRequest $request,
        AccountUserBlockForUserAction $action,
    ) {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        $result = $action->block($user, (int) $request->validated('blocked_user_id'));

        return ApiResponse::success($result['block']);
    }

    public function unblockUserForUser(int $blockedUserId, AccountUserBlockForUserAction $action)
    {
        /** @var AccountUser $user */
        $user = auth('user')->user();

        return ApiResponse::success($action->unblock($user, $blockedUserId));
    }
}
