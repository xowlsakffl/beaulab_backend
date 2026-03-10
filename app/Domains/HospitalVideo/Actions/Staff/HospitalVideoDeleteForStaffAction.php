<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

            $this->query->softDelete($video);
            $video->refresh();

            return [
                'deleted_id' => (int) $video->id,
                'deleted_at' => optional($video->deleted_at)?->toISOString(),
            ];
        });
    }
}
