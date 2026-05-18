<?php

namespace App\Domains\Chat\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Policies\Staff\ChatMessageForStaffPolicy;

final class ChatMessagePolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, ChatMessage $message): bool
    {
        return $this->delegate($actor)->view($actor, $message);
    }

    public function update(mixed $actor, ?ChatMessage $message = null): bool
    {
        return $this->delegate($actor)->update($actor, $message);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(ChatMessageForStaffPolicy::class),
            $actor instanceof AccountUser => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, ChatMessage $message): bool
                {
                    return false;
                }

                public function update(mixed $actor, ?ChatMessage $message = null): bool
                {
                    return false;
                }
            },
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, ChatMessage $message): bool
                {
                    return false;
                }

                public function update(mixed $actor, ?ChatMessage $message = null): bool
                {
                    return false;
                }
            },
        };
    }
}
