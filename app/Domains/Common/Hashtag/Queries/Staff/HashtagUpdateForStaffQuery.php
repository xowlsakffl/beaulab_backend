<?php

namespace App\Domains\Common\Hashtag\Queries\Staff;

use App\Domains\Common\Hashtag\Models\Hashtag;

/**
 * HashtagUpdateForStaffQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HashtagUpdateForStaffQuery
{
    public function update(Hashtag $hashtag, array $data): Hashtag
    {
        $hashtag->fill($data);

        if ($hashtag->isDirty()) {
            $hashtag->save();
        }

        return $hashtag->fresh();
    }
}
