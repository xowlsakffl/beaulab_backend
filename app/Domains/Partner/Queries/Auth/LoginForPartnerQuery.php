<?php

namespace App\Domains\Partner\Queries\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Partner\Models\AccountPartner;
use Illuminate\Support\Facades\Hash;

final class LoginForPartnerQuery
{
    /**
     * @param array{nickname:string,password:string,device_name?:string|null} $data
     * @return array{token:string, partner: AccountPartner, roles: list<string>, permissions: list<string>}
     */
    public function login(array $data): array
    {
        $partner = AccountPartner::query()
            ->where('nickname', $data['nickname'])
            ->first();

        if (! $partner) {
            throw new CustomException(errorCode: ErrorCode::USER_NOT_FOUND);
        }

        if (! Hash::check($data['password'], $partner->password)) {
            throw new CustomException(
                errorCode: ErrorCode::UNAUTHORIZED,
                message: '아이디 또는 비밀번호가 일치하지 않습니다.'
            );
        }

        if (! $partner->isActive()) {
            throw new CustomException(
                errorCode: ErrorCode::FORBIDDEN,
                message: '비활성화된 계정입니다.'
            );
        }

        $partner->forceFill([
            'last_login_at' => now(),
        ])->save();

        $tokenName = $data['device_name'] ?? 'partner-web';

        $token = $partner
            ->createToken($tokenName, ['actor:partner', '*'])
            ->plainTextToken;

        return [
            'token' => $token,
            'partner' => $partner,
            'roles' => $partner->getRoleNames()->values()->all(),
            'permissions' => $partner->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
