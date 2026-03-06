<?php

namespace App\Domains\Common\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Policies\Staff\CategoryForStaffPolicy;

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

