<?php

namespace App\Domains\AccountBeauty\Queries\Beauty;

use App\Common\Auth\LoginCredentials;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use Illuminate\Support\Facades\DB;

/**
 * 뷰티 계정 로그인 Query.
 * nickname/password/계정 상태를 검증한다.
 */
final class LoginForAccountBeautyQuery
{
    public function __construct(private readonly LoginCredentials $credentials) {}

    /**
     * @param  array{nickname:string,password:string}  $data
     * @return array{beauty: AccountBeauty, roles: list<string>, permissions: list<string>}
     */
    public function login(array $data): array
    {
        return DB::transaction(fn (): array => $this->loginInTransaction($data));
    }

    /**
     * @param  array{nickname:string,password:string}  $data
     * @return array{beauty: AccountBeauty, roles: list<string>, permissions: list<string>}
     */
    private function loginInTransaction(array $data): array
    {
        $beauty = AccountBeauty::query()
            ->where('nickname', $data['nickname'])
            ->first();

        $this->credentials->validate($beauty, $data['password']);

        if (! $beauty->isActive()) {
            throw new CustomException(
                errorCode: ErrorCode::FORBIDDEN,
                message: '비활성화된 계정입니다.'
            );
        }

        $beauty->forceFill([
            'last_login_at' => now(),
        ])->save();

        return [
            'beauty' => $beauty,
            'roles' => $beauty->getRoleNames()->values()->all(),
            'permissions' => $beauty->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
