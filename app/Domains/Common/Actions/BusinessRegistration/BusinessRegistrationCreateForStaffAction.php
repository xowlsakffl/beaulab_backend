<?php

namespace App\Domains\Common\Actions\BusinessRegistration;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Common\Models\BusinessRegistration\BusinessRegistration;
use App\Domains\Common\Queries\BusinessRegistration\BusinessRegistrationCreateForStaffQuery;
use App\Domains\Hospital\Models\Hospital;
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

        $businessRegistrationMedia = $this->mediaAttachAction->attachCertificate(
            $owner,
            $payload['business_registration_file'],
            $ownerType
        );

        return $this->query->create([
            'owner_type' => $ownerType,
            'owner_id' => $owner->id,
            'business_number' => $payload['business_number'],
            'company_name' => $owner->name,
            'ceo_name' => $payload['ceo_name'],
            'business_type' => $payload['business_type'],
            'business_item' => $payload['business_item'],
            'business_address' => $payload['business_address'],
            'business_address_detail' => $payload['business_address_detail'],
            'certificate_media_id' => $businessRegistrationMedia->id,
            'issued_at' => $payload['issued_at'],
            'status' => BusinessRegistration::STATUS_ACTIVE,
        ]);
    }
}
