<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Domains\AccountHospital\Queries\HospitalAccountPasswordResetQuery;
use App\Domains\AccountHospital\Support\HospitalAccountPasswordResetGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class HospitalAccountPasswordResetAction
{
    public function __construct(
        private readonly HospitalAccountPasswordResetGuard $guard,
        private readonly HospitalAccountPasswordResetQuery $query,
    ) {}

    /** @return array{message:string} */
    public function execute(string $token, string $password): array
    {
        $accountId = DB::transaction(function () use ($token, $password): int {
            [$account, $reset] = $this->guard->lockValid($token);
            $this->query->complete($account, $reset, $password);

            return (int) $account->getKey();
        }, 3);

        Log::info('병의원 계정 비밀번호 재설정 완료', ['account_hospital_id' => $accountId]);

        return ['message' => '비밀번호가 변경되었습니다.'];
    }
}
