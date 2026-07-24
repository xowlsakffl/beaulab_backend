<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\AccountUser;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Actions\Staff\AccountUserGetForStaffAction;
use App\Domains\AccountUser\Actions\Staff\AccountUserListForStaffAction;
use App\Domains\AccountUser\Actions\Staff\AccountUserStatusUpdateForStaffAction;
use App\Domains\AccountUser\Actions\Staff\AccountUserSummaryForStaffAction;
use App\Domains\AccountUser\Models\AccountUser;
use App\Modules\Staff\Http\Requests\AccountUser\AccountUserListForStaffRequest;
use App\Modules\Staff\Http\Requests\AccountUser\AccountUserStatusUpdateForStaffRequest;

/**
 * AccountUserForStaffController 역할 정의.
 * 일반 회원 계정 도메인의 HTTP 컨트롤러로, 라우트 요청을 받아 Request 검증 결과와 Action 실행 결과를 API 응답으로 연결한다.
 */
final class AccountUserForStaffController extends Controller
{
    /**
     * GET /api/v1/staff/users
     * (Beaulab) Staff 전용 일반회원 목록
     */
    public function getAccountUsersForStaff(
        AccountUserListForStaffRequest $request,
        AccountUserListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    /**
     * GET /api/v1/staff/users/summary
     * (Beaulab) Staff 전용 일반회원 목록 상단 집계
     */
    public function getAccountUserSummaryForStaff(AccountUserSummaryForStaffAction $action)
    {
        return ApiResponse::success($action->execute());
    }

    /**
     * GET /api/v1/staff/users/{user}
     * (Beaulab) Staff 전용 일반회원 단건 조회
     */
    public function getAccountUserForStaff(
        AccountUser $user,
        AccountUserGetForStaffAction $action,
    ) {
        $result = $action->execute($user);

        return ApiResponse::success($result['user']);
    }

    /**
     * PATCH /api/v1/staff/users/{user}/status
     * (Beaulab) Staff 전용 일반회원 상태 변경
     */
    public function updateAccountUserStatusForStaff(
        AccountUser $user,
        AccountUserStatusUpdateForStaffRequest $request,
        AccountUserStatusUpdateForStaffAction $action,
    ) {
        $result = $action->execute($user, $request->validated());

        return ApiResponse::success($result['user']);
    }
}
