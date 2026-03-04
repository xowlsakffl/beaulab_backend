<?php

namespace App\Domains\HospitalBusinessRegistration\Actions;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\HospitalBusinessRegistration\Models\HospitalBusinessRegistration;
use App\Domains\HospitalBusinessRegistration\Queries\HospitalBusinessRegistrationCreateForStaffQuery;
use Illuminate\Database\Eloquent\Model;

final class HospitalBusinessRegistrationCreateForStaffAction
{
    public function __construct(
        private readonly HospitalBusinessRegistrationCreateForStaffQuery $query,
        private readonly MediaAttachAction                               $mediaAttachAction,
    ) {}

    public function execute(Model $owner, array $payload): HospitalBusinessRegistration
    {
        $ownerType = strtolower(class_basename($owner));

        $businessRegistration = $this->query->create([
            'hospital_id' => $owner->id,
            'business_number' => $payload['business_number'],
            'company_name' => $owner->name,
            'ceo_name' => $payload['ceo_name'],
            'business_type' => $payload['business_type'],
            'business_item' => $payload['business_item'],
            'business_address' => $payload['business_address'],
            'business_address_detail' => $payload['business_address_detail'],
            'issued_at' => $payload['issued_at'],
            'status' => HospitalBusinessRegistration::STATUS_ACTIVE,
        ]);

        $this->mediaAttachAction->attachOne($businessRegistration, $payload['business_registration_file'], 'business_registration_file', $ownerType, 'business-registration');

        return $businessRegistration;
    }
}
