<?php

namespace App\Domains\Common\Media\Queries;

use App\Domains\Common\Media\Models\Media;
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

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Media>
     */
    public function collectionMedia(Model $owner, string $collection)
    {
        return Media::query()
            ->for($owner)
            ->collection($collection)
            ->get();
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }
}
