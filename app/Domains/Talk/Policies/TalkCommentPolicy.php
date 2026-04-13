<?php

namespace App\Domains\Talk\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Policies\Staff\TalkCommentForStaffPolicy;

/**
 * TalkCommentPolicy 역할 정의.
 * 토크 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
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
