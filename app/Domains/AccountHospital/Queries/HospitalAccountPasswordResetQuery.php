<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Queries;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Models\HospitalAccountPasswordReset;
use Illuminate\Support\Facades\Hash;

final class HospitalAccountPasswordResetQuery
{
    public function lockAccountForHospital(int $hospitalId): ?AccountHospital
    {
        return AccountHospital::query()->with('hospital')->where('hospital_id', $hospitalId)->lockForUpdate()->first();
    }

    public function lockAccount(int $accountId): ?AccountHospital
    {
        return AccountHospital::query()->with('hospital')->lockForUpdate()->find($accountId);
    }

    public function findByHash(string $hash, bool $lock = false): ?HospitalAccountPasswordReset
    {
        return HospitalAccountPasswordReset::query()->where('token_hash', $hash)
            ->when($lock, fn ($query) => $query->lockForUpdate())->first();
    }

    public function latest(int $accountId): ?HospitalAccountPasswordReset
    {
        return HospitalAccountPasswordReset::query()->where('account_hospital_id', $accountId)->whereNull('revoked_at')->latest('id')->first();
    }

    public function revokePending(int $accountId): void
    {
        HospitalAccountPasswordReset::query()->where('account_hospital_id', $accountId)
            ->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    public function create(array $attributes): HospitalAccountPasswordReset
    {
        return HospitalAccountPasswordReset::query()->create($attributes);
    }

    public function complete(AccountHospital $account, HospitalAccountPasswordReset $reset, string $password): void
    {
        // 비밀번호 해시가 fillable 감사 로그에 남지 않도록 원자적으로 갱신한다.
        AccountHospital::query()->whereKey($account->getKey())->update([
            'password' => Hash::make($password),
            'updated_at' => now(),
        ]);
        $reset->forceFill(['used_at' => now()])->save();
        $this->revokePending((int) $account->getKey());
        $account->tokens()->delete();
    }
}
