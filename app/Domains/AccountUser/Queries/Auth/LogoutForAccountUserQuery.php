<?php

namespace App\Domains\AccountUser\Queries\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

final class LogoutForAccountUserQuery
{
    public function deleteCurrentToken(?Authenticatable $actor): void
    {
        $actor?->currentAccessToken()?->delete();
    }
}
