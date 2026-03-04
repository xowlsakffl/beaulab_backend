<?php

namespace App\Domains\HospitalVideoRequest\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use App\Domains\HospitalVideoRequest\Queries\Staff\HospitalVideoRequestDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoRequestDeleteForStaffAction
{
    public function __construct(
        private readonly HospitalVideoRequestDeleteForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(HospitalVideoRequest $videoRequest): array
    {
        Gate::authorize('delete', $videoRequest);

        return DB::transaction(function () use ($videoRequest) {
            $this->mediaAttachAction->deleteCollectionMediaBulk($videoRequest, [
                'source_video_file',
                'source_thumbnail_file',
            ]);

            $this->query->softDelete($videoRequest);
            $videoRequest->refresh();

            return [
                'deleted_id' => (int) $videoRequest->id,
                'deleted_at' => optional($videoRequest->deleted_at)?->toISOString(),
            ];
        });
    }
}
