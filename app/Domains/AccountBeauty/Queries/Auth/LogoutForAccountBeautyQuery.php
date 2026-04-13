<?php

namespace App\Domains\AccountBeauty\Queries\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

final class LogoutForAccountBeautyQuery
{
    public function deleteCurrentToken(?Authenticatable $actor): void
    {
        $actor?->currentAccessToken()?->delete();
    }
}
