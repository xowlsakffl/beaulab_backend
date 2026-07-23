<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
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

        $result = DB::transaction(function () use ($video) {
            $hashtagIds = $video->hashtags()
                ->pluck('hashtags.id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->all();

            $this->mediaAttachAction->deleteCollectionMedia($video, 'thumbnail_file');
            $video->categories()->sync([]);
            $video->hashtags()->sync([]);
            Hashtag::syncUsageCounts($hashtagIds);

            $this->query->softDelete($video);
            $video->refresh();

            return [
                'deleted_id' => (int) $video->id,
                'deleted_at' => optional($video->deleted_at)?->toISOString(),
            ];
        });

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_VIDEO);

        return $result;
    }
}
