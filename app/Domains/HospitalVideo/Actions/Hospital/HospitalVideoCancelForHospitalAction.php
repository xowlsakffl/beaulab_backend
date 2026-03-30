<?php

namespace App\Domains\HospitalVideo\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalVideo\Dto\Hospital\HospitalVideoForHospitalDetailDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Hospital\HospitalVideoCancelForHospitalQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoCancelForHospitalAction
{
    public function __construct(
        private readonly HospitalVideoCancelForHospitalQuery $query,
    ) {}

    public function execute(HospitalVideo $video): array
    {
        Gate::authorize('cancel', $video);

        if (! in_array($video->allow_status, [
            HospitalVideo::ALLOW_SUBMITTED,
        ], true)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '검수중인 요청은 처리할 수 없습니다.');
        }

        $video = DB::transaction(fn () => $this->query->cancel($video)->load([
            'thumbnailMedia',
            'videoFileMedia',
            'categories',
        ]));

        return [
            'video' => HospitalVideoForHospitalDetailDto::fromModel($video)->toArray(),
        ];
    }
}
