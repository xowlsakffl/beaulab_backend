<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalBusinessRegistration;
use App\Domains\Hospital\Queries\Staff\HospitalBusinessRegistrationUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;

/**
 * HospitalBusinessRegistrationUpdateForStaffAction 역할 정의.
 * 병원 사업자 등록 수정 흐름과 증빙 파일 교체를 조합한다.
 */
final class HospitalBusinessRegistrationUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalBusinessRegistrationUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(Hospital $hospital, array $payload): void
    {
        $businessRegistration = $this->query->findForHospital($hospital);

        if (! $businessRegistration instanceof HospitalBusinessRegistration) {
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
            'settlement_bank_name',
            'settlement_account_number',
            'settlement_account_holder',
            'tax_invoice_email',
            'issued_at',
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
            $this->mediaAttachAction->attachOne($businessRegistration, $payload['business_registration_file'], 'business_registration_file', 'hospital', 'business-registration');
        } elseif (array_key_exists('existing_business_registration_file_id', $payload) && empty($payload['existing_business_registration_file_id'])) {
            $this->deleteCertificateMedia($businessRegistration);
        }
    }

    private function deleteCertificateMedia(HospitalBusinessRegistration $businessRegistration): void
    {
        $existingCertificate = $businessRegistration->certificateMedia()->first();

        if (! $existingCertificate instanceof Media) {
            return;
        }

        $this->mediaAttachAction->delete($existingCertificate);
    }
}
