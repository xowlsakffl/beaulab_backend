<?php

namespace App\Domains\Common\Hashtag\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Hashtag\Dto\Staff\HashtagForStaffDto;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Hashtag\Queries\Staff\HashtagGetForStaffQuery;
use App\Domains\Common\Hashtag\Queries\Staff\HashtagUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * HashtagUpdateForStaffAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HashtagUpdateForStaffAction
{
    public function __construct(
        private readonly HashtagUpdateForStaffQuery $query,
        private readonly HashtagGetForStaffQuery $detailQuery,
        private readonly HashtagUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(Hashtag $hashtag, array $payload): array
    {
        Gate::authorize('update', $hashtag);
        if (array_key_exists('status', $payload)) {
            Gate::authorize('updateStatus', $hashtag);
        }

        $updated = DB::transaction(function () use ($hashtag, $payload) {
            $hashtag = Hashtag::query()->lockForUpdate()->findOrFail($hashtag->getKey());
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
                'status' => $status,
            ];

            $before = $this->historyRecordAction->capture($hashtag);
            $updated = $this->query->update($hashtag, $updateData);
            $this->historyRecordAction->recordUpdated($updated, $before);

            return $updated;
        });

        return [
            'hashtag' => HashtagForStaffDto::fromModel(
                $this->detailQuery->get($updated)
            )->toArray(),
        ];
    }
}
