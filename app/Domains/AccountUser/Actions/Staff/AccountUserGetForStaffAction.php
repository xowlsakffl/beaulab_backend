<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Actions\Staff;

use App\Domains\AccountUser\Dto\Staff\AccountUserForStaffDetailDto;
use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Support\Facades\Gate;

final class AccountUserGetForStaffAction
{
    public function execute(AccountUser $user): array
    {
        Gate::authorize('view', $user);

        return [
            'user' => AccountUserForStaffDetailDto::fromModel($user)->toArray(),
        ];
    }
}
