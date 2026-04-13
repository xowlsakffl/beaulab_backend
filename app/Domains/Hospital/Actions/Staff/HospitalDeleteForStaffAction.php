<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * HospitalDeleteForStaffAction 역할 정의.
 * 병원 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalDeleteForStaffAction
{
    public function __construct(
        private readonly HospitalDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction     $mediaAttachAction,
    ) {}

    public function execute(Hospital $hospital): array
    {
        Gate::authorize('delete', $hospital);

        Log::info('병원 삭제(soft delete) 실행', [
            'hospital_id' => $hospital->id,
        ]);

        return DB::transaction(function () use ($hospital) {
            $this->mediaAttachAction->deleteCollectionMediaBulk($hospital, ['logo', 'gallery']);
            $hospital->categories()->sync([]);

            if ($hospital->businessRegistration) {
                $this->mediaAttachAction->deleteCollectionMedia($hospital->businessRegistration, 'business_registration_file');
            }

            $hospital->doctors()->get()->each(function ($doctor): void {
                $this->mediaAttachAction->deleteCollectionMediaBulk($doctor, [
                    'profile_image',
                    'license_image',
                    'specialist_certificate_image',
                    'education_certificate_image',
                    'etc_certificate_image',
                ]);
            });

            $this->query->softDelete($hospital);

            // soft delete 후 deleted_at 값 최신화
            $hospital->refresh();

            return [
                'deleted_id' => (int) $hospital->id,
                'deleted_at' => optional($hospital->deleted_at)?->toISOString(),
            ];
        });
    }
}
