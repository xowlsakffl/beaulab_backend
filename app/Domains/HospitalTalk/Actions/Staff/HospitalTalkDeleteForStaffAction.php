<?php

namespace App\Domains\HospitalTalk\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Domains\HospitalTalk\Queries\Staff\HospitalTalkDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalTalkDeleteForStaffAction
{
    public function __construct(
        private readonly HospitalTalkDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(HospitalTalk $talk): array
    {
        Gate::authorize('delete', $talk);

        return DB::transaction(function () use ($talk) {
            $this->mediaAttachAction->deleteCollectionMedia($talk, 'images');
            $talk->categories()->sync([]);

            $this->query->softDelete($talk);
            $talk->refresh();

            return [
                'deleted_id' => (int) $talk->id,
                'deleted_at' => optional($talk->deleted_at)?->toISOString(),
            ];
        });
    }
}
