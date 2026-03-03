<?php

declare(strict_types=1);

namespace App\Domains\User\Policies\Partner;

use App\Domains\Partner\Models\AccountPartner;
use App\Domains\User\Models\AccountUser;

final class AccountUserForPartnerPolicy
{
    public function viewAny(AccountPartner $actor): bool
    {
        return false;
    }

    public function view(AccountPartner $actor, AccountUser $user): bool
    {
        return false;
    }

    public function update(AccountPartner $actor, AccountUser $user): bool
    {
        return false;
    }

    public function delete(AccountPartner $actor, AccountUser $user): bool
    {
        return false;
    }
}
