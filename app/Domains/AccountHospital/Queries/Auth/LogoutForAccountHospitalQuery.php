<?php

namespace App\Domains\AccountHospital\Queries\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

final class LogoutForAccountHospitalQuery
{
    public function deleteCurrentToken(?Authenticatable $actor): void
    {
        $actor?->currentAccessToken()?->delete();
    }
}
