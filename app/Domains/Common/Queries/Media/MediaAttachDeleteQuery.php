<?php

namespace App\Domains\Common\Queries\Media;

use App\Domains\Common\Models\Media\Media;
use Illuminate\Database\Eloquent\Model;

/**
 * MediaAttachDeleteQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class MediaAttachDeleteQuery
{
    public function create(array $data): Media
    {
        return Media::create($data);
    }

    public function clearPrimary(Model $owner, string $collection): int
    {
        $rows = Media::query()
            ->where('model_type', $owner::class)
            ->where('model_id', $owner->getKey())
            ->where('collection', $collection)
            ->where('is_primary', true)
            ->get();

        foreach ($rows as $row) {
            $row->forceFill(['is_primary' => false])->save();
        }

        return $rows->count();
    }
}
