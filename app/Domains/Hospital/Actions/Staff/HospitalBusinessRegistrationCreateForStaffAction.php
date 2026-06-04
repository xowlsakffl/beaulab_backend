<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalBusinessRegistration;
use App\Domains\Hospital\Queries\Staff\HospitalBusinessRegistrationCreateForStaffQuery;

/**
 * HospitalBusinessRegistrationCreateForStaffAction 역할 정의.
 * 병원 사업자 등록 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalBusinessRegistrationCreateForStaffAction
{
    public function __construct(
        private readonly HospitalBusinessRegistrationCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(Hospital $owner, array $payload): HospitalBusinessRegistration
    {
        $businessRegistration = $this->query->create([
            'hospital_id' => $owner->id,
            'business_number' => $payload['business_number'],
            'company_name' => $payload['company_name'],
            'ceo_name' => $payload['ceo_name'],
            'business_type' => $payload['business_type'],
            'business_item' => $payload['business_item'],
            'business_address' => $payload['business_address'] ?? null,
            'business_address_detail' => $payload['business_address_detail'] ?? null,
            'settlement_bank_name' => $payload['settlement_bank_name'] ?? null,
            'settlement_account_number' => $payload['settlement_account_number'] ?? null,
            'settlement_account_holder' => $payload['settlement_account_holder'] ?? null,
            'tax_invoice_email' => $payload['tax_invoice_email'] ?? null,
            'issued_at' => $payload['issued_at'] ?? null,
            'status' => HospitalBusinessRegistration::STATUS_ACTIVE,
        ]);

        $this->mediaAttachAction->attachOne($businessRegistration, $payload['business_registration_file'], 'business_registration_file', 'hospital', 'business-registration');

        return $businessRegistration;
    }
}
