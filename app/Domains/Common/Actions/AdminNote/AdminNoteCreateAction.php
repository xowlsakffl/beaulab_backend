<?php

namespace App\Domains\Common\Actions\AdminNote;

use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\Common\Queries\AdminNote\AdminNoteCreateQuery;
use Illuminate\Database\Eloquent\Model;

/**
 * AdminNoteCreateAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class AdminNoteCreateAction
{
    public function __construct(
        private readonly AdminNoteCreateQuery $query,
    ) {}

    public function execute(
        Model $target,
        string $note,
        ?Model $creator = null,
        bool $isInternal = true,
    ): AdminNote {
        return $this->query->create([
            'target_type' => $target::class,
            'target_id' => (int) $target->getKey(),
            'note' => trim($note),
            'is_internal' => $isInternal,
            'creator_type' => $creator?->getMorphClass(),
            'creator_id' => $creator ? (int) $creator->getKey() : null,
        ]);
    }
}
