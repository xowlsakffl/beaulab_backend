<?php

namespace App\Domains\Common\Hashtag\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Hashtag\Dto\Staff\HashtagForStaffDto;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Hashtag\Queries\Staff\HashtagUpdateForStaffQuery;
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
        $exists = $this->query->existsNormalizedName($hashtag, $normalizedName);

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
            'hashtag' => HashtagForStaffDto::fromModel($updated)->toArray(),
        ];
    }
}
