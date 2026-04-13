<?php

namespace App\Domains\Common\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Policies\Staff\CategoryForStaffPolicy;

/**
 * CategoryPolicy 역할 정의.
 * 공통 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class CategoryPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Category $category): bool
    {
        return $this->delegate($actor)->view($actor, $category);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Category $category): bool
    {
        return $this->delegate($actor)->update($actor, $category);
    }

    public function delete(mixed $actor, Category $category): bool
    {
        return $this->delegate($actor)->delete($actor, $category);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(CategoryForStaffPolicy::class),
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Category $category): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Category $category): bool { return false; }
                public function delete(mixed $actor, Category $category): bool { return false; }
            },
        };
    }
}

