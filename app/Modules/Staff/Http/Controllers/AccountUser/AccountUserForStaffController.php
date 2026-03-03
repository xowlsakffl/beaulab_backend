<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\AccountUser;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\AccountUser\Actions\Staff\AccountUserDeleteForStaffAction;
use App\Domains\AccountUser\Actions\Staff\AccountUserGetForStaffAction;
use App\Domains\AccountUser\Actions\Staff\AccountUserListForStaffAction;
use App\Domains\AccountUser\Actions\Staff\AccountUserUpdateForStaffAction;
use App\Domains\AccountUser\Models\AccountUser;
use App\Modules\Staff\Http\Requests\AccountUser\AccountUserListForStaffRequest;
use App\Modules\Staff\Http\Requests\AccountUser\AccountUserUpdateForStaffRequest;

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
     * GET /api/v1/staff/users/{user}
     * (Beaulab) Staff 전용 일반회원 단건 조회
     */
    public function getAccountUserForStaff(
        AccountUser $user,
        AccountUserGetForStaffAction $action,
    ) {
        $result = $action->execute($user);

        return ApiResponse::success($result['user'] ?? $result);
    }

    /**
     * PATCH /api/v1/staff/users/{user}
     * (Beaulab) Staff 전용 일반회원 수정
     */
    public function updateAccountUserForStaff(
        AccountUser $user,
        AccountUserUpdateForStaffRequest $request,
        AccountUserUpdateForStaffAction $action,
    ) {
        $result = $action->execute($user, $request->validated());

        return ApiResponse::success($result['user'] ?? $result);
    }

    /**
     * DELETE /api/v1/staff/users/{user}
     * (Beaulab) Staff 전용 일반회원 삭제
     */
    public function deleteAccountUserForStaff(
        AccountUser $user,
        AccountUserDeleteForStaffAction $action,
    ) {
        $result = $action->execute($user);

        return ApiResponse::success($result);
    }
}
