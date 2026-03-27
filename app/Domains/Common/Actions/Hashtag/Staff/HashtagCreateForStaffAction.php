<?php

namespace App\Domains\Common\Actions\Hashtag\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Models\Hashtag\Hashtag;
use App\Domains\Common\Queries\Hashtag\Staff\HashtagCreateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class HashtagCreateForStaffAction
{
    public function __construct(
        private readonly HashtagCreateForStaffQuery $query,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Hashtag::class);

        $name = Hashtag::sanitizeName((string) ($payload['name'] ?? ''));
        $normalizedName = Hashtag::normalizeName($name);
        $status = Hashtag::normalizeStatus((string) ($payload['status'] ?? Hashtag::STATUS_ACTIVE));

        if ($name === '' || $normalizedName === '') {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '해시태그명은 필수입니다.');
        }

        $exists = Hashtag::query()
            ->where('normalized_name', $normalizedName)
            ->exists();

        if ($exists) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '동일한 해시태그가 이미 존재합니다.');
        }

        $createData = [
            'name' => $name,
            'normalized_name' => $normalizedName,
        ];

        if (Hashtag::supportsStatus()) {
            $createData['status'] = $status;
        }

        if (Hashtag::supportsUsageCount()) {
            $createData['usage_count'] = 0;
        }

        $created = DB::transaction(fn () => $this->query->create($createData)->fresh());

        Log::info('해시태그 생성', [
            'hashtag_id' => $created->id,
            'name' => $created->name,
            'normalized_name' => $created->normalized_name,
            'status' => $created->resolveStatus($status),
        ]);

        return [
            'hashtag' => $this->toArray($created),
        ];
    }

    private function toArray(Hashtag $hashtag): array
    {
        return [
            'id' => (int) $hashtag->id,
            'name' => (string) $hashtag->name,
            'normalized_name' => (string) $hashtag->normalized_name,
            'status' => $hashtag->resolveStatus(),
            'usage_count' => $hashtag->resolveUsageCount(0),
            'assignment_count' => 0,
            'created_at' => optional($hashtag->created_at)?->toISOString(),
            'updated_at' => optional($hashtag->updated_at)?->toISOString(),
        ];
    }
}
