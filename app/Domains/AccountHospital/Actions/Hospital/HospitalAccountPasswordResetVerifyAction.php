<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Domains\AccountHospital\Support\HospitalAccountPasswordResetGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class HospitalAccountPasswordResetVerifyAction
{
    public function __construct(private readonly HospitalAccountPasswordResetGuard $guard) {}

    /** @return array{valid:bool,hospital_name:string,masked_nickname:string} */
    public function execute(string $token): array
    {
        return DB::transaction(function () use ($token): array {
            [$account] = $this->guard->lockValid($token);
            $nickname = (string) $account->nickname;
            $visibleLength = min(3, max(0, mb_strlen($nickname) - 2));

            return [
                'valid' => true,
                'hospital_name' => (string) $account->hospital->name,
                'masked_nickname' => Str::mask($nickname, '*', $visibleLength),
            ];
        }, 3);
    }
}
