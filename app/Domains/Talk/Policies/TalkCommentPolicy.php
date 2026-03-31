<?php

namespace App\Domains\Talk\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Policies\Staff\TalkCommentForStaffPolicy;

final class TalkCommentPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, TalkComment $comment): bool
    {
        return $this->delegate($actor)->view($actor, $comment);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, TalkComment $comment): bool
    {
        return $this->delegate($actor)->update($actor, $comment);
    }

    public function delete(mixed $actor, TalkComment $comment): bool
    {
        return $this->delegate($actor)->delete($actor, $comment);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(TalkCommentForStaffPolicy::class),
            $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, TalkComment $comment): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, TalkComment $comment): bool { return false; }
                public function delete(mixed $actor, TalkComment $comment): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, TalkComment $comment): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, TalkComment $comment): bool { return false; }
                public function delete(mixed $actor, TalkComment $comment): bool { return false; }
            },
        };
    }
}
