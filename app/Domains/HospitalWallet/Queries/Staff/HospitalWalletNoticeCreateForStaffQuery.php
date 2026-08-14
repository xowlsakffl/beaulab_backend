<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Queries\Staff;

use App\Domains\HospitalWallet\Models\HospitalWallet;
use Illuminate\Support\Collection;

final class HospitalWalletNoticeCreateForStaffQuery
{
    /**
     * @param  list<int>  $hospitalIds
     * @return Collection<int, HospitalWallet>
     */
    public function getWalletsForUpdate(array $hospitalIds): Collection
    {
        return HospitalWallet::query()
            ->whereIn('hospital_id', $hospitalIds)
            ->with(['hospital.accountHospital'])
            ->orderBy('hospital_id')
            ->lockForUpdate()
            ->get();
    }
}
