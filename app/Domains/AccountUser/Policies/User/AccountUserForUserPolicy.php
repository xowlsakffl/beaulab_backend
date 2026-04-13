<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Policies\User;

use App\Domains\AccountUser\Models\AccountUser;

/**
 * AccountUserForUserPolicy 역할 정의.
 * 일반 회원 계정 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class AccountUserForUserPolicy
{
    public function viewAny(AccountUser $actor): bool
    {
        return false;
    }

    public function view(AccountUser $actor, AccountUser $user): bool
    {
        return $actor->id === $user->id;
    }

    public function update(AccountUser $actor, AccountUser $user): bool
    {
        return $actor->id === $user->id;
    }

    public function delete(AccountUser $actor, AccountUser $user): bool
    {
        return $actor->id === $user->id;
    }
}
