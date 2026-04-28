<?php

namespace App\Domains\Common\Hashtag\Actions\Staff;

use App\Domains\Common\Hashtag\Dto\Staff\HashtagForStaffDto;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Hashtag\Queries\Staff\HashtagGetForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * HashtagGetForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HashtagGetForStaffAction
{
    public function __construct(
        private readonly HashtagGetForStaffQuery $query,
    ) {}

    public function execute(Hashtag $hashtag): array
    {
        Gate::authorize('view', $hashtag);

        Log::info('해시태그 단건 조회', [
            'hashtag_id' => $hashtag->id,
        ]);

        $detail = $this->query->get($hashtag);

        return [
            'hashtag' => HashtagForStaffDto::fromModel($detail)->toArray(),
        ];
    }
}
