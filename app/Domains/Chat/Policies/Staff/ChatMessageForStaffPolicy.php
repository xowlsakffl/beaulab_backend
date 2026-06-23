<?php

namespace App\Domains\Chat\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Chat\Models\ChatMessage;

final class ChatMessageForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW);
    }

    public function view(AccountStaff $actor, ChatMessage $message): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_SHOW);
    }

    public function update(AccountStaff $actor, ?ChatMessage $message = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_REPORTED_CHAT_MESSAGE_UPDATE);
    }
}
