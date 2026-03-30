<?php

namespace App\Domains\Common\Actions\AdminNote;

use App\Domains\Common\Dto\AdminNote\AdminNoteData;
use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\Common\Queries\AdminNote\AdminNoteListQuery;
use App\Domains\Common\Support\AdminNote\AdminNoteTargetRegistry;
use Illuminate\Support\Facades\Gate;

final class AdminNoteListAction
{
    public function __construct(
        private readonly AdminNoteListQuery $query,
    ) {}

    public function execute(mixed $actor, array $filters): array
    {
        $target = AdminNoteTargetRegistry::resolveTarget(
            (string) $filters['target_type'],
            (int) $filters['target_id'],
        );

        Gate::forUser($actor)->authorize('view', $target);

        $items = $this->query->getForTarget($target, $actor)
            ->map(static fn (AdminNote $note): array => AdminNoteData::fromModel($note)->toArray())
            ->values()
            ->all();

        return [
            'items' => $items,
        ];
    }
}
