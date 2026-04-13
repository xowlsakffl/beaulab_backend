<?php

namespace App\Domains\Common\Actions\Hashtag\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Models\Hashtag\Hashtag;
use App\Domains\Common\Queries\Hashtag\Staff\HashtagUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * HashtagUpdateForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HashtagUpdateForStaffAction
{
    public function __construct(
        private readonly HashtagUpdateForStaffQuery $query,
    ) {}

    public function execute(Hashtag $hashtag, array $payload): array
    {
        Gate::authorize('update', $hashtag);

        $name = array_key_exists('name', $payload)
            ? Hashtag::sanitizeName((string) $payload['name'])
            : (string) $hashtag->name;
        $normalizedName = Hashtag::normalizeName($name);
        $status = array_key_exists('status', $payload)
            ? Hashtag::normalizeStatus((string) $payload['status'])
            : $hashtag->resolveStatus();

        if ($name === '' || $normalizedName === '') {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '해시태그명은 비워둘 수 없습니다.');
        }

        $exists = Hashtag::query()
            ->where('normalized_name', $normalizedName)
            ->whereKeyNot($hashtag->id)
            ->exists();

        if ($exists) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '동일한 해시태그가 이미 존재합니다.');
        }

        $updateData = [
            'name' => $name,
            'normalized_name' => $normalizedName,
        ];

        if (Hashtag::supportsStatus()) {
            $updateData['status'] = $status;
        }

        $updated = DB::transaction(fn () => $this->query->update($hashtag, $updateData));

        Log::info('해시태그 수정', [
            'hashtag_id' => $updated->id,
            'name' => $updated->name,
            'normalized_name' => $updated->normalized_name,
            'status' => $updated->resolveStatus($status),
        ]);

        return [
            'hashtag' => $this->toArray($updated),
        ];
    }

    private function toArray(Hashtag $hashtag): array
    {
        $assignmentCount = $this->resolveAssignmentCount($hashtag);

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

    private function resolveAssignmentCount(Hashtag $hashtag): int
    {
        if (array_key_exists('assignment_count', $hashtag->getAttributes())) {
            return (int) ($hashtag->getAttribute('assignment_count') ?? 0);
        }

        return (int) DB::table('hashtaggables')
            ->where('hashtag_id', $hashtag->id)
            ->count();
    }
}
