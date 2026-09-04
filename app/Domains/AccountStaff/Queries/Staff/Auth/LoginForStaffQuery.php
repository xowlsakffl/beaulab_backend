<?php

namespace App\Domains\AccountStaff\Queries\Staff\Auth;

use App\Common\Auth\LoginCredentials;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Support\Facades\DB;

/**
 * 스태프 로그인 Query.
 * nickname/password/계정 상태를 검증한다.
 */
final class LoginForStaffQuery
{
    public function __construct(private readonly LoginCredentials $credentials) {}

    /**
     * @param  array{nickname:string,password:string}  $data
     * @return array{staff: AccountStaff, roles: list<string>, permissions: list<string>}
     */
    public function login(array $data): array
    {
        return DB::transaction(fn (): array => $this->loginInTransaction($data));
    }

    /**
     * @param  array{nickname:string,password:string}  $data
     * @return array{staff: AccountStaff, roles: list<string>, permissions: list<string>}
     */
    private function loginInTransaction(array $data): array
    {
        $staff = AccountStaff::query()
            ->where('nickname', $data['nickname'])
            ->first();

        $this->credentials->validate($staff, $data['password']);

        if (! $staff->isActive()) {
            throw new CustomException(
                errorCode: ErrorCode::FORBIDDEN,
                message: '비활성화된 계정입니다.'
            );
        }

        $staff->forceFill([
            'last_login_at' => now(),
        ])->save();

        return [
            'staff' => $staff,
            'roles' => $staff->getRoleNames()->values()->all(),
            'permissions' => $staff->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
