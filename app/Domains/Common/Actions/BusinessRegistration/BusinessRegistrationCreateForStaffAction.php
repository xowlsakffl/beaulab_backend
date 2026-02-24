<?php

namespace App\Domains\Common\Actions\BusinessRegistration;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Common\Models\BusinessRegistration\BusinessRegistration;
use App\Domains\Common\Queries\BusinessRegistration\BusinessRegistrationCreateForStaffQuery;
use Illuminate\Database\Eloquent\Model;

final class BusinessRegistrationCreateForStaffAction
{
    public function __construct(
        private readonly BusinessRegistrationCreateForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(Model $owner, array $payload): BusinessRegistration
    {
        $ownerType = strtolower(class_basename($owner));

        $businessRegistration = $this->query->create([
            'owner_type' => $ownerType,
            'owner_id' => $owner->id,
            'business_number' => $payload['business_number'],
            'company_name' => $owner->name,
            'ceo_name' => $payload['ceo_name'],
            'business_type' => $payload['business_type'],
            'business_item' => $payload['business_item'],
            'business_address' => $payload['business_address'],
            'business_address_detail' => $payload['business_address_detail'],
            'issued_at' => $payload['issued_at'],
            'status' => BusinessRegistration::STATUS_ACTIVE,
        ]);

        $this->mediaAttachAction->attachCertificate(
            $businessRegistration,
            $payload['business_registration_file'],
            $ownerType,
        );

        return $businessRegistration;
    }
}
