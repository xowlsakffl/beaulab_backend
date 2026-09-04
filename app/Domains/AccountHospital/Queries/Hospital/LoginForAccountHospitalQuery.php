<?php

namespace App\Domains\AccountHospital\Queries\Hospital;

use App\Common\Auth\LoginCredentials;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use Illuminate\Support\Facades\DB;

/**
 * 병원 계정 로그인 Query.
 * nickname/password/계정 상태를 검증한다.
 */
final class LoginForAccountHospitalQuery
{
    public function __construct(private readonly LoginCredentials $credentials) {}

    /**
     * @param  array{nickname:string,password:string}  $data
     * @return array{hospital: AccountHospital, roles: list<string>, permissions: list<string>}
     */
    public function login(array $data): array
    {
        $result = DB::transaction(fn (): array => $this->loginInTransaction($data));

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL);

        return $result;
    }

    /**
     * @param  array{nickname:string,password:string}  $data
     * @return array{hospital: AccountHospital, roles: list<string>, permissions: list<string>}
     */
    private function loginInTransaction(array $data): array
    {
        $hospital = AccountHospital::query()
            ->where('nickname', $data['nickname'])
            ->first();

        $this->credentials->validate($hospital, $data['password']);

        if (! $hospital->isActive()) {
            throw new CustomException(
                errorCode: ErrorCode::FORBIDDEN,
                message: '비활성화된 계정입니다.'
            );
        }

        $hospital->forceFill([
            'last_login_at' => now(),
        ])->save();

        return [
            'hospital' => $hospital,
            'roles' => $hospital->getRoleNames()->values()->all(),
            'permissions' => $hospital->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
