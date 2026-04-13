<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\BeautyExpert\Queries\Staff\BeautyExpertDeleteForStaffQuery;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * BeautyExpertDeleteForStaffAction 역할 정의.
 * 뷰티 전문가 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
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
            $expert->categories()->sync([]);

            $this->query->softDelete($expert);
            $expert->refresh();

            return [
                'deleted_id' => (int) $expert->id,
                'deleted_at' => optional($expert->deleted_at)?->toISOString(),
            ];
        });
    }
}
