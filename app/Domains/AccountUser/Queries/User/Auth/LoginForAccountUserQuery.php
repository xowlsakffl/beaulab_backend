<?php

namespace App\Domains\AccountUser\Queries\User\Auth;

use App\Common\Auth\LoginCredentials;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Support\Facades\DB;

/**
 * 앱 사용자 로그인 Query.
 * 이메일/비밀번호/계정 상태를 검증한다.
 */
final class LoginForAccountUserQuery
{
    public function __construct(private readonly LoginCredentials $credentials) {}

    /**
     * @param  array{email:string,password:string,device_name?:string|null}  $data
     * @return array{user: AccountUser}
     */
    public function login(array $data): array
    {
        return DB::transaction(fn (): array => $this->loginInTransaction($data));
    }

    /**
     * @param  array{email:string,password:string,device_name?:string|null}  $data
     * @return array{user: AccountUser}
     */
    private function loginInTransaction(array $data): array
    {
        $user = AccountUser::query()
            ->where('email', $data['email'])
            ->first();

        $this->credentials->validate($user, $data['password']);

        if (! $user->isActive()) {
            throw new CustomException(
                errorCode: ErrorCode::FORBIDDEN,
                message: '비활성화된 계정입니다.'
            );
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return [
            'user' => $user,
        ];
    }
}
