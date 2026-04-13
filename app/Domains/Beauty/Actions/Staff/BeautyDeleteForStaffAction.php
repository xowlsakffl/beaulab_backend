<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Queries\Staff\BeautyDeleteForStaffQuery;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * BeautyDeleteForStaffAction 역할 정의.
 * 뷰티 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyDeleteForStaffAction
{
    public function __construct(
        private readonly BeautyDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction   $mediaAttachAction,
    ) {}

    public function execute(Beauty $beauty): array
    {
        Gate::authorize('delete', $beauty);

        Log::info('뷰티업체 삭제(soft delete) 실행', [
            'beauty_id' => $beauty->id,
        ]);

        return DB::transaction(function () use ($beauty) {
            $this->mediaAttachAction->deleteCollectionMediaBulk($beauty, ['logo', 'gallery']);
            $beauty->categories()->sync([]);

            if ($beauty->businessRegistration) {
                $this->mediaAttachAction->deleteCollectionMedia($beauty->businessRegistration, 'business_registration_file');
            }

            $beauty->experts()->get()->each(function ($expert): void {
                $this->mediaAttachAction->deleteCollectionMediaBulk($expert, [
                    'profile_image',
                    'education_certificate_image',
                    'etc_certificate_image',
                ]);
            });

            $this->query->softDelete($beauty);

            // soft delete 후 deleted_at 값 최신화
            $beauty->refresh();

            return [
                'deleted_id' => (int) $beauty->id,
                'deleted_at' => optional($beauty->deleted_at)?->toISOString(),
            ];
        });
    }
}
