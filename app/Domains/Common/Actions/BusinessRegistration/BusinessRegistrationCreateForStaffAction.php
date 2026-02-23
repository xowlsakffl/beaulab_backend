<?php

namespace App\Domains\Common\Actions\BusinessRegistration;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Common\Models\BusinessRegistration;
use App\Domains\Common\Queries\BusinessRegistration\BusinessRegistrationCreateForStaffQuery;
use App\Domains\Hospital\Models\Hospital;

final class BusinessRegistrationCreateForStaffAction
{
    public function __construct(
        private readonly BusinessRegistrationCreateForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(Hospital $hospital, array $payload): BusinessRegistration
    {
        $certificateMedia = $this->mediaAttachAction->attachCertificate(
            $hospital,
            $payload['business_registration_certificate'],
            'hospital'
        );

        return $this->query->create([
            'owner_type' => 'hospital',
            'owner_id' => $hospital->id,
            'business_number' => preg_replace('/\D+/', '', (string) $payload['business_number']) ?: $payload['business_number'],
            'company_name' => $hospital->name,
            'ceo_name' => $payload['ceo_name'],
            'business_type' => $payload['business_type'],
            'business_item' => $payload['business_item'],
            'certificate_media_id' => $certificateMedia->id,
            'status' => 'ACTIVE',
        ]);
    }
}
