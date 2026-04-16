<?php

namespace App\Modules\User\Http\Controllers\Talk;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Actions\User\TalkSaveForUserAction;
use App\Domains\Talk\Models\Talk;

/**
 * 앱 사용자 토크 저장 API 컨트롤러.
 */
final class TalkSaveForUserController extends Controller
{
    public function saveTalkForUser(Talk $talk, TalkSaveForUserAction $action)
    {
        $result = $action->save($this->user(), $talk);

        return ApiResponse::success($result['save'] ?? $result);
    }

    public function unsaveTalkForUser(Talk $talk, TalkSaveForUserAction $action)
    {
        $result = $action->unsave($this->user(), $talk);

        return ApiResponse::success($result['save'] ?? $result);
    }

    private function user(): AccountUser
    {
        $user = auth()->user();

        if (! $user instanceof AccountUser) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        if (! $user->isActive()) {
            throw new CustomException(ErrorCode::FORBIDDEN, '활성 상태의 사용자만 토크를 저장할 수 있습니다.');
        }

        return $user;
    }
}
