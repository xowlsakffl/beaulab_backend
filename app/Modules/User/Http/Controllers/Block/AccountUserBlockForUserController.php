<?php

namespace App\Modules\User\Http\Controllers\Block;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
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
        $result = $action->list($this->user(), $request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    public function blockUserForUser(
        AccountUserBlockCreateForUserRequest $request,
        AccountUserBlockForUserAction $action,
    ) {
        $result = $action->block($this->user(), (int) $request->validated('blocked_user_id'));

        return ApiResponse::success($result['block']);
    }

    public function unblockUserForUser(int $blockedUserId, AccountUserBlockForUserAction $action)
    {
        return ApiResponse::success($action->unblock($this->user(), $blockedUserId));
    }

    private function user(): AccountUser
    {
        $user = auth()->user();

        // 차단 API는 앱 사용자 계정만 접근 가능해야 다른 actor 토큰과 섞이지 않는다.
        if (! $user instanceof AccountUser) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        if (! $user->isActive()) {
            throw new CustomException(ErrorCode::FORBIDDEN, '활성 상태의 사용자만 차단 기능을 사용할 수 있습니다.');
        }

        return $user;
    }
}
