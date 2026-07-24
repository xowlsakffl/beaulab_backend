<?php

namespace App\Domains\Common\Hashtag\Queries\Staff;

use App\Domains\Common\Hashtag\Models\Hashtag;

/**
 * HashtagResolveForStaffQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, 해시태그 식별/생성에 필요한 DB 접근을 캡슐화한다.
 */
final class HashtagResolveForStaffQuery
{
    public function findByNormalizedName(string $normalizedName): ?Hashtag
    {
        return Hashtag::query()
            ->where('normalized_name', $normalizedName)
            ->first();
    }

    public function create(array $data): Hashtag
    {
        return Hashtag::create($data);
    }
}
