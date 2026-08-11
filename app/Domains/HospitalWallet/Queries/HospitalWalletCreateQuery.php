<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalWallet\Models\HospitalWallet;

/**
 * HospitalWalletCreateQuery 역할 정의.
 * 신규 병의원의 0P 지갑을 생성한다.
 */
final class HospitalWalletCreateQuery
{
    public function createForHospital(Hospital $hospital): HospitalWallet
    {
        return HospitalWallet::query()->create([
            'hospital_id' => $hospital->getKey(),
            'paid_balance' => 0,
            'service_balance' => 0,
            'last_transaction_at' => null,
        ]);
    }
}
