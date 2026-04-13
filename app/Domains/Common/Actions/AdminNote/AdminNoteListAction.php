<?php

namespace App\Domains\Common\Actions\AdminNote;

use App\Domains\Common\Dto\AdminNote\AdminNoteData;
use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\Common\Queries\AdminNote\AdminNoteListQuery;
use App\Domains\Common\Support\AdminNote\AdminNoteTargetRegistry;
use Illuminate\Support\Facades\Gate;

/**
 * AdminNoteListAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
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
