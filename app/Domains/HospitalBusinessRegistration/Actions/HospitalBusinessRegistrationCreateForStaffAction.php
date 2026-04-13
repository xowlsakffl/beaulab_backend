<?php

namespace App\Domains\HospitalBusinessRegistration\Actions;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalBusinessRegistration\Models\HospitalBusinessRegistration;
use App\Domains\HospitalBusinessRegistration\Queries\HospitalBusinessRegistrationCreateForStaffQuery;
use Illuminate\Database\Eloquent\Model;

/**
 * HospitalBusinessRegistrationCreateForStaffAction 역할 정의.
 * 병원 사업자 등록 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalBusinessRegistrationCreateForStaffAction
{
    public function __construct(
        private readonly HospitalBusinessRegistrationCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction                         $mediaAttachAction,
    ) {}

    public function execute(Model $owner, array $payload): HospitalBusinessRegistration
    {
        $ownerType = strtolower(class_basename($owner));

        $businessRegistration = $this->query->create([
            'hospital_id' => $owner->id,
            'business_number' => $payload['business_number'],
            'company_name' => $payload['company_name'],
            'ceo_name' => $payload['ceo_name'],
            'business_type' => $payload['business_type'],
            'business_item' => $payload['business_item'],
            'business_address' => $payload['business_address'] ?? null,
            'business_address_detail' => $payload['business_address_detail'] ?? null,
            'issued_at' => $payload['issued_at'] ?? null,
            'status' => HospitalBusinessRegistration::STATUS_ACTIVE,
        ]);

        $this->mediaAttachAction->attachOne($businessRegistration, $payload['business_registration_file'], 'business_registration_file', $ownerType, 'business-registration');

        return $businessRegistration;
    }
}
