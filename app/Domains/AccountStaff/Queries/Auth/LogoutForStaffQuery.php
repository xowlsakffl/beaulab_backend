<?php

namespace App\Domains\AccountStaff\Queries\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

final class LogoutForStaffQuery
{
    public function deleteCurrentToken(?Authenticatable $actor): void
    {
        $actor?->currentAccessToken()?->delete();
    }
}
