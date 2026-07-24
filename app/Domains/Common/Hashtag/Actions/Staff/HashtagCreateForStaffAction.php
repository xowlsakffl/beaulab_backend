<?php

namespace App\Domains\Common\Hashtag\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Hashtag\Dto\Staff\HashtagForStaffDto;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Hashtag\Queries\Staff\HashtagCreateForStaffQuery;
use App\Domains\Common\Hashtag\Queries\Staff\HashtagGetForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * HashtagCreateForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HashtagCreateForStaffAction
{
    public function __construct(
        private readonly HashtagCreateForStaffQuery $query,
        private readonly HashtagGetForStaffQuery $detailQuery,
        private readonly HashtagUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Hashtag::class);

        $name = Hashtag::sanitizeName((string) ($payload['name'] ?? ''));
        $normalizedName = Hashtag::normalizeName($name);
        $status = Hashtag::normalizeStatus((string) ($payload['status'] ?? Hashtag::STATUS_ACTIVE));
        $exists = $this->query->existsNormalizedName($normalizedName);

        if ($exists) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '동일한 해시태그가 이미 존재합니다.');
        }

        $createData = [
            'name' => $name,
            'normalized_name' => $normalizedName,
            'status' => $status,
            'usage_count' => 0,
        ];

        $created = DB::transaction(function () use ($createData) {
            $created = $this->query->create($createData)->fresh();
            $this->historyRecordAction->recordCreated($created);

            return $created;
        });

        return [
            'hashtag' => HashtagForStaffDto::fromModel(
                $this->detailQuery->get($created)
            )->toArray(),
        ];
    }
}
