<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Models\BeautyBusinessRegistration;
use App\Domains\Beauty\Queries\Staff\BeautyBusinessRegistrationUpdateForStaffQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\Media\Models\Media;
use Illuminate\Http\UploadedFile;

/**
 * BeautyBusinessRegistrationUpdateForStaffAction 역할 정의.
 * 뷰티 사업자 등록 수정 흐름과 증빙 파일 교체를 조합한다.
 */
final class BeautyBusinessRegistrationUpdateForStaffAction
{
    public function __construct(
        private readonly BeautyBusinessRegistrationUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(Beauty $beauty, array $payload): void
    {
        $businessRegistration = $this->query->findForBeauty($beauty);

        if (! $businessRegistration instanceof BeautyBusinessRegistration) {
            return;
        }

        $updates = [];

        foreach ([
            'business_number',
            'company_name',
            'ceo_name',
            'business_type',
            'business_item',
            'business_address',
            'business_address_detail',
        ] as $field) {
            if (array_key_exists($field, $payload)) {
                $updates[$field] = $payload[$field];
            }
        }

        if ($updates !== []) {
            $this->query->update($businessRegistration, $updates);
        }

        if (($payload['business_registration_file'] ?? null) instanceof UploadedFile) {
            $this->deleteCertificateMedia($businessRegistration);
            $this->mediaAttachAction->attachOne($businessRegistration, $payload['business_registration_file'], 'business_registration_file', 'beauty', 'business-registration');
        }
    }

    private function deleteCertificateMedia(BeautyBusinessRegistration $businessRegistration): void
    {
        $existingCertificate = $businessRegistration->certificateMedia()->first();

        if (! $existingCertificate instanceof Media) {
            return;
        }

        $this->mediaAttachAction->delete($existingCertificate);
    }
}
