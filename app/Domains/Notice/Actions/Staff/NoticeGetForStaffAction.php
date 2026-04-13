<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\Notice\Dto\Staff\NoticeForStaffDetailDto;
use App\Domains\Notice\Models\Notice;
use Illuminate\Support\Facades\Gate;

/**
 * NoticeGetForStaffAction 역할 정의.
 * 공지사항 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class NoticeGetForStaffAction
{
    public function execute(Notice $notice): array
    {
        Gate::authorize('view', $notice);

        $loaded = $notice->load([
            'attachments',
        ]);

        return [
            'notice' => NoticeForStaffDetailDto::fromModel($loaded)->toArray(),
        ];
    }
}
