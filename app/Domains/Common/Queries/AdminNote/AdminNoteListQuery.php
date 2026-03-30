<?php

namespace App\Domains\Common\Queries\AdminNote;

use App\Domains\Common\Models\AdminNote\AdminNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class AdminNoteListQuery
{
    /**
     * @return Collection<int, AdminNote>
     */
    public function getForTarget(Model $target, mixed $actor): Collection
    {
        return AdminNote::query()
            ->forTarget($target)
            ->visibleTo($actor)
            ->with('creator')
            ->latest('id')
            ->get();
    }
}
