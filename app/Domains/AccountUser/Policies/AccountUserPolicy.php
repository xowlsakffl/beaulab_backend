<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Policies\Staff\AccountUserForStaffPolicy;
use App\Domains\AccountUser\Policies\User\AccountUserForUserPolicy;

/**
 * AccountUserPolicy 역할 정의.
 * 일반 회원 계정 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class AccountUserPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, AccountUser $user): bool
    {
        return $this->delegate($actor)->view($actor, $user);
    }

    public function update(mixed $actor, AccountUser $user): bool
    {
        return $this->delegate($actor)->update($actor, $user);
    }

    public function delete(mixed $actor, AccountUser $user): bool
    {
        return $this->delegate($actor)->delete($actor, $user);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(AccountUserForStaffPolicy::class),
            //$actor instanceof AccountPartner => app(AccountUserForPartnerPolicy::class),
            $actor instanceof AccountUser => app(AccountUserForUserPolicy::class),
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, AccountUser $user): bool { return false; }
                public function update(mixed $actor, AccountUser $user): bool { return false; }
                public function delete(mixed $actor, AccountUser $user): bool { return false; }
            },
        };
    }
}
