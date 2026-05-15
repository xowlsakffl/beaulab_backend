<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Talk\Dto\Staff\TalkForStaffDetailDto;
use App\Domains\Talk\Models\Talk;
use Illuminate\Support\Facades\Gate;

/**
 * TalkGetForStaffAction 역할 정의.
 * 토크 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class TalkGetForStaffAction
{
    public function execute(Talk $talk, array $filters = []): array
    {
        Gate::authorize('view', $talk);

        $talk->load([
            'author',
            'categories',
            'images',
            'poll.options',
        ]);

        return [
            'talk' => TalkForStaffDetailDto::fromModel($talk)->toArray(),
        ];
    }
}
