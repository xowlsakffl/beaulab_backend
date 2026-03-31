<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\Staff\TalkDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class TalkDeleteForStaffAction
{
    public function __construct(
        private readonly TalkDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(Talk $talk): array
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
