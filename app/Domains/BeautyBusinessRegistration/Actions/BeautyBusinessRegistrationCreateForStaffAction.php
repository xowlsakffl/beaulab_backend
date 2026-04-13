<?php

namespace App\Domains\BeautyBusinessRegistration\Actions;

use App\Domains\BeautyBusinessRegistration\Models\BeautyBusinessRegistration;
use App\Domains\BeautyBusinessRegistration\Queries\BeautyBusinessRegistrationCreateForStaffQuery;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use Illuminate\Database\Eloquent\Model;

/**
 * BeautyBusinessRegistrationCreateForStaffAction 역할 정의.
 * 뷰티 사업자 등록 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyBusinessRegistrationCreateForStaffAction
{
    public function __construct(
        private readonly BeautyBusinessRegistrationCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction                       $mediaAttachAction,
    ) {}

    public function execute(Model $owner, array $payload): BeautyBusinessRegistration
    {
        $ownerType = strtolower(class_basename($owner));

        $businessRegistration = $this->query->create([
            'beauty_id' => $owner->id,
            'business_number' => $payload['business_number'],
            'company_name' => $owner->name,
            'ceo_name' => $payload['ceo_name'],
            'business_type' => $payload['business_type'],
            'business_item' => $payload['business_item'],
            'business_address' => $payload['business_address'],
            'business_address_detail' => $payload['business_address_detail'],
            'issued_at' => $payload['issued_at'],
            'status' => BeautyBusinessRegistration::STATUS_ACTIVE,
        ]);

        $this->mediaAttachAction->attachOne($businessRegistration, $payload['business_registration_file'], 'business_registration_file', $ownerType, 'business-registration');

        return $businessRegistration;
    }
}
