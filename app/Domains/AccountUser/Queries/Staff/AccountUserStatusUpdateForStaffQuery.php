<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;

final class AccountUserStatusUpdateForStaffQuery
{
    public function update(AccountUser $user, string $status): AccountUser
    {
        $user->status = $status;
        $user->blocked_at = $status === AccountUser::STATUS_BLOCKED
            ? ($user->blocked_at ?? now())
            : null;

        if ($user->isDirty(['status', 'blocked_at'])) {
            $user->save();
        }

        return $user->fresh();
    }
}
