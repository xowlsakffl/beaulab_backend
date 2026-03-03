<?php

namespace App\Domains\BeautyBusinessRegistration\Actions;

use App\Domains\BeautyBusinessRegistration\Models\BeautyBusinessRegistration;
use App\Domains\BeautyBusinessRegistration\Queries\BeautyBusinessRegistrationCreateForStaffQuery;
use App\Domains\Common\Actions\Media\MediaAttachAction;
use Illuminate\Database\Eloquent\Model;

final class BeautyBusinessRegistrationCreateForStaffAction
{
    public function __construct(
        private readonly BeautyBusinessRegistrationCreateForStaffQuery $query,
        private readonly MediaAttachAction                               $mediaAttachAction,
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

        $this->mediaAttachAction->attachCertificate(
            $businessRegistration,
            $payload['business_registration_file'],
            $ownerType,
        );

        return $businessRegistration;
    }
}
