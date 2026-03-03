<?php

namespace App\Domains\AccountHospital\Queries\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Support\Facades\Hash;

final class LoginForAccountHospitalQuery
{
    /**
     * @param array{nickname:string,password:string,device_name?:string|null} $data
     * @return array{token:string, hospital: AccountHospital, roles: list<string>, permissions: list<string>}
     */
    public function login(array $data): array
    {
        $hospital = AccountHospital::query()
            ->where('nickname', $data['nickname'])
            ->first();

        if (! $hospital) {
            throw new CustomException(errorCode: ErrorCode::USER_NOT_FOUND);
        }

        if (! Hash::check($data['password'], $hospital->password)) {
            throw new CustomException(
                errorCode: ErrorCode::UNAUTHORIZED,
                message: '아이디 또는 비밀번호가 일치하지 않습니다.'
            );
        }

        if (! $hospital->isActive()) {
            throw new CustomException(
                errorCode: ErrorCode::FORBIDDEN,
                message: '비활성화된 계정입니다.'
            );
        }

        $hospital->forceFill([
            'last_login_at' => now(),
        ])->save();

        $tokenName = $data['device_name'] ?? 'hospital-web';

        $token = $hospital
            ->createToken($tokenName, ['actor:hospital', '*'])
            ->plainTextToken;

        return [
            'token' => $token,
            'hospital' => $hospital,
            'roles' => $hospital->getRoleNames()->values()->all(),
            'permissions' => $hospital->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
