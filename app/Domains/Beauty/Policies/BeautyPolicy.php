<?php

namespace App\Domains\Beauty\Policies;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Policies\Beauty\BeautyForBeautyPolicy;
use App\Domains\Beauty\Policies\Staff\BeautyForStaffPolicy;
use App\Domains\AccountStaff\Models\AccountStaff;

/**
 * BeautyPolicy 역할 정의.
 * 뷰티 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class BeautyPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Beauty $beauty): bool
    {
        return $this->delegate($actor)->view($actor, $beauty);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Beauty $beauty): bool
    {
        return $this->delegate($actor)->update($actor, $beauty);
    }

    public function delete(mixed $actor, Beauty $beauty): bool
    {
        return $this->delegate($actor)->delete($actor, $beauty);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff   => app(BeautyForStaffPolicy::class),
            $actor instanceof AccountBeauty  => app(BeautyForBeautyPolicy::class),

            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Beauty $beauty): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Beauty $beauty): bool { return false; }
                public function delete(mixed $actor, Beauty $beauty): bool { return false; }
            },
        };
    }
}
