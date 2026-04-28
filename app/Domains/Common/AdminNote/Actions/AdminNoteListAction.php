<?php

namespace App\Domains\Common\AdminNote\Actions;

use App\Domains\Common\AdminNote\Dto\AdminNoteDto;
use App\Domains\Common\AdminNote\Models\AdminNote;
use App\Domains\Common\AdminNote\Queries\AdminNoteListQuery;
use App\Domains\Common\AdminNote\Support\AdminNoteTargetRegistry;
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
            ->map(static fn (AdminNote $note): array => AdminNoteDto::fromModel($note)->toArray())
            ->values()
            ->all();

        return [
            'items' => $items,
        ];
    }
}
