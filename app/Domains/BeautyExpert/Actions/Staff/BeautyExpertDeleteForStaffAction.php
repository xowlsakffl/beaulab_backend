<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\BeautyExpert\Queries\Staff\BeautyExpertDeleteForStaffQuery;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class BeautyExpertDeleteForStaffAction
{
    public function __construct(
        private readonly BeautyExpertDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction         $mediaAttachAction,
    ) {}

    public function execute(BeautyExpert $expert): array
    {
        Gate::authorize('delete', $expert);

        return DB::transaction(function () use ($expert) {
            $this->mediaAttachAction->deleteCollectionMediaBulk($expert, [
                'profile_image',
                'education_certificate_image',
                'etc_certificate_image',
            ]);

            $this->query->softDelete($expert);
            $expert->refresh();

            return [
                'deleted_id' => (int) $expert->id,
                'deleted_at' => optional($expert->deleted_at)?->toISOString(),
            ];
        });
    }
}
