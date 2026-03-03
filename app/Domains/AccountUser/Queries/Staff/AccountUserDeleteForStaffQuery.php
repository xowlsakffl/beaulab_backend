<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;

final class AccountUserDeleteForStaffQuery
{
    public function softDelete(AccountUser $user): void
    {
        $user->delete();
    }
}
