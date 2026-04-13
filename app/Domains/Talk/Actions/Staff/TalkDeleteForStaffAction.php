<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\Staff\TalkDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * TalkDeleteForStaffAction 역할 정의.
 * 토크 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class TalkDeleteForStaffAction
{
    public function __construct(
        private readonly TalkDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(Talk $talk): array
    {
        Gate::authorize('delete', $talk);

        return DB::transaction(function () use ($talk) {
            $this->mediaAttachAction->deleteCollectionMedia($talk, 'images');
            $talk->categories()->sync([]);

            $this->query->softDelete($talk);
            $talk->refresh();

            return [
                'deleted_id' => (int) $talk->id,
                'deleted_at' => optional($talk->deleted_at)?->toISOString(),
            ];
        });
    }
}
