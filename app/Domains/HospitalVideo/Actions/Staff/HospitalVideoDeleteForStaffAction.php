<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalVideoDeleteForStaffAction 역할 정의.
 * 병원 동영상 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalVideoDeleteForStaffAction
{
    public function __construct(
        private readonly HospitalVideoDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(HospitalVideo $video): array
    {
        Gate::authorize('delete', $video);

        return DB::transaction(function () use ($video) {
            $this->mediaAttachAction->deleteCollectionMedia($video, 'thumbnail_file');
            $this->mediaAttachAction->deleteCollectionMedia($video, 'video_file');
            $video->categories()->sync([]);

            $this->query->softDelete($video);
            $video->refresh();

            return [
                'deleted_id' => (int) $video->id,
                'deleted_at' => optional($video->deleted_at)?->toISOString(),
            ];
        });
    }
}
