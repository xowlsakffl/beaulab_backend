<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalWallet\Models\HospitalWallet;

final class HospitalWalletForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_SHOW);
    }

    public function view(AccountStaff $actor, HospitalWallet $wallet): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_SHOW);
    }
}
