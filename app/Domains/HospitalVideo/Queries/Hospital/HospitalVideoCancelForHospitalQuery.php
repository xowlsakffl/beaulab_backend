<?php

namespace App\Domains\HospitalVideo\Queries\Hospital;

use App\Domains\HospitalVideo\Models\HospitalVideo;

/**
 * HospitalVideoCancelForHospitalQuery 역할 정의.
 * 병원 동영상 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalVideoCancelForHospitalQuery
{
    public function cancel(HospitalVideo $video): HospitalVideo
    {
        $video->fill([
            'status' => HospitalVideo::STATUS_INACTIVE,
            'allow_status' => HospitalVideo::ALLOW_PARTNER_CANCELED,
        ]);

        if ($video->isDirty()) {
            $video->save();
        }

        return $video->fresh();
    }
}
