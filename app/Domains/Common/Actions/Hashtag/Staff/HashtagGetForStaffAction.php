<?php

namespace App\Domains\Common\Actions\Hashtag\Staff;

use App\Domains\Common\Models\Hashtag\Hashtag;
use App\Domains\Common\Queries\Hashtag\Staff\HashtagGetForStaffQuery;
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
            'hashtag' => $this->toArray($detail),
        ];
    }

    private function toArray(Hashtag $hashtag): array
    {
        $assignmentCount = (int) ($hashtag->getAttribute('assignment_count') ?? 0);

        return [
            'id' => (int) $hashtag->id,
            'name' => (string) $hashtag->name,
            'normalized_name' => (string) $hashtag->normalized_name,
            'status' => $hashtag->resolveStatus(),
            'usage_count' => $hashtag->resolveUsageCount($assignmentCount),
            'assignment_count' => $assignmentCount,
            'created_at' => optional($hashtag->created_at)?->toISOString(),
            'updated_at' => optional($hashtag->updated_at)?->toISOString(),
        ];
    }
}
