<?php

namespace App\Domains\Beauty\Policies\Beauty;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\Beauty\Models\Beauty;

final class BeautyForBeautyPolicy
{
    public function viewAny(AccountBeauty $actor): bool
    {
        return $actor->can(AccessPermissions::BEAUTY_PROFILE_SHOW);
    }

    public function view(AccountBeauty $actor, Beauty $beauty): bool
    {
        return $actor->can(AccessPermissions::BEAUTY_PROFILE_SHOW) && $this->ownsBeauty($actor, $beauty);
    }

    public function create(AccountBeauty $actor): bool
    {
        return false;
    }

    public function update(AccountBeauty $actor, Beauty $beauty): bool
    {
        return $actor->can(AccessPermissions::BEAUTY_PROFILE_UPDATE) && $this->ownsBeauty($actor, $beauty);
    }

    public function delete(AccountBeauty $actor, Beauty $beauty): bool
    {
        return false;
    }

    private function ownsBeauty(AccountBeauty $actor, Beauty $beauty): bool
    {
        return (int) $actor->beauty_id > 0 && (int) $actor->beauty_id === (int) $beauty->id;
    }
}
