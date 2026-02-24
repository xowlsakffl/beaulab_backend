<?php

declare(strict_types=1);

namespace App\Domains\User\Queries\Staff;

use App\Domains\User\Models\AccountUser;

final class AccountUserDeleteForStaffQuery
{
    public function softDelete(AccountUser $user): void
    {
        $user->delete();
    }
}
