<?php

namespace App\Domains\AccountStaff\Queries\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Support\Facades\Hash;

final class LoginForStaffQuery
{
    /**
     * @param array{nickname:string,password:string} $data
     * @return array{token:string, staff: AccountStaff, roles: list<string>, permissions: list<string>}
     */
    public function login(array $data): array
    {
        $staff = AccountStaff::query()
            ->where('nickname', $data['nickname'])
            ->first();

        // 아이디 없음
        if (!$staff) {
            throw new CustomException(
                errorCode: ErrorCode::USER_NOT_FOUND
            );
        }

        // 비밀번호 틀림
        if (!Hash::check($data['password'], $staff->password)) {
            throw new CustomException(
                errorCode: ErrorCode::UNAUTHORIZED,
                message: '아이디 또는 비밀번호가 일치하지 않습니다.'
            );
        }

        // 계정 비활성
        if (!$staff->isActive()) {
            throw new CustomException(
                errorCode: ErrorCode::FORBIDDEN,
                message: '비활성화된 계정입니다.'
            );
        }

        $staff->forceFill([
            'last_login_at' => now(),
        ])->save();

        $token = $staff
            ->createToken('staff-web', ['actor:staff'])
            ->plainTextToken;

        return [
            'token' => $token,
            'staff' => $staff,
            'roles' => $staff->getRoleNames()->values()->all(),
            'permissions' => $staff->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
