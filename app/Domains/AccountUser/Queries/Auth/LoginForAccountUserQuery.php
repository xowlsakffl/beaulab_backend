<?php

namespace App\Domains\AccountUser\Queries\Auth;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class LoginForAccountUserQuery
{
    /**
     * @param array{email:string,password:string,device_name?:string|null} $data
     * @return array{token:string, user: AccountUser, roles: list<string>, permissions: list<string>}
     */
    public function login(array $data): array
    {
        return DB::transaction(fn (): array => $this->loginInTransaction($data));
    }

    /**
     * @param array{email:string,password:string,device_name?:string|null} $data
     * @return array{token:string, user: AccountUser, roles: list<string>, permissions: list<string>}
     */
    private function loginInTransaction(array $data): array
    {
        $user = AccountUser::query()
            ->where('email', $data['email'])
            ->first();

        if (! $user instanceof AccountUser) {
            throw new CustomException(ErrorCode::USER_NOT_FOUND);
        }

        if (! Hash::check($data['password'], $user->password)) {
            throw new CustomException(
                errorCode: ErrorCode::UNAUTHORIZED,
                message: '이메일 또는 비밀번호가 일치하지 않습니다.'
            );
        }

        if (! $user->isActive()) {
            throw new CustomException(
                errorCode: ErrorCode::FORBIDDEN,
                message: '비활성화된 계정입니다.'
            );
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $tokenName = $data['device_name'] ?? 'user-app';

        $token = $user
            ->createToken($tokenName, ['actor:user'])
            ->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
