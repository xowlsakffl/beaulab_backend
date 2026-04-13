<?php

namespace App\Domains\AccountBeauty\Queries\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * 뷰티 계정 로그인 Query.
 * nickname/password/계정 상태를 검증하고 Sanctum actor:beauty 토큰을 발급한다.
 */
final class LoginForAccountBeautyQuery
{
    /**
     * @param array{nickname:string,password:string,device_name?:string|null} $data
     * @return array{token:string, beauty: AccountBeauty, roles: list<string>, permissions: list<string>}
     */
    public function login(array $data): array
    {
        return DB::transaction(fn (): array => $this->loginInTransaction($data));
    }

    /**
     * @param array{nickname:string,password:string,device_name?:string|null} $data
     * @return array{token:string, beauty: AccountBeauty, roles: list<string>, permissions: list<string>}
     */
    private function loginInTransaction(array $data): array
    {
        $beauty = AccountBeauty::query()
            ->where('nickname', $data['nickname'])
            ->first();

        if (! $beauty) {
            throw new CustomException(errorCode: ErrorCode::USER_NOT_FOUND);
        }

        if (! Hash::check($data['password'], $beauty->password)) {
            throw new CustomException(
                errorCode: ErrorCode::UNAUTHORIZED,
                message: '아이디 또는 비밀번호가 일치하지 않습니다.'
            );
        }

        if (! $beauty->isActive()) {
            throw new CustomException(
                errorCode: ErrorCode::FORBIDDEN,
                message: '비활성화된 계정입니다.'
            );
        }

        $beauty->forceFill([
            'last_login_at' => now(),
        ])->save();

        $tokenName = $data['device_name'] ?? 'beauty-web';

        $token = $beauty
            ->createToken($tokenName, ['actor:beauty'])
            ->plainTextToken;

        return [
            'token' => $token,
            'beauty' => $beauty,
            'roles' => $beauty->getRoleNames()->values()->all(),
            'permissions' => $beauty->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
