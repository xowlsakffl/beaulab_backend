<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Queries\Staff;

use App\Domains\HospitalEntry\Models\HospitalEntry;

final class HospitalEntryUpdateForStaffQuery
{
    public function update(HospitalEntry $entry, array $payload): HospitalEntry
    {
        $entry->fill([
            'hospital_name' => $payload['hospital_name'] ?? $entry->hospital_name,
            'hospital_phone' => $payload['hospital_phone'] ?? $entry->hospital_phone,
            'address' => $payload['address'] ?? $entry->address,
            'address_detail' => array_key_exists('address_detail', $payload) ? $payload['address_detail'] : $entry->address_detail,
            'business_number' => $payload['business_number'] ?? $entry->business_number,
            'ceo_name' => $payload['ceo_name'] ?? $entry->ceo_name,
            'license_number' => array_key_exists('license_number', $payload) ? $payload['license_number'] : $entry->license_number,
            'applicant_name' => $payload['applicant_name'] ?? $entry->applicant_name,
            'applicant_position' => array_key_exists('applicant_position', $payload) ? $payload['applicant_position'] : $entry->applicant_position,
            'applicant_phone' => array_key_exists('applicant_phone', $payload) ? $payload['applicant_phone'] : $entry->applicant_phone,
            'applicant_email' => array_key_exists('applicant_email', $payload) ? $payload['applicant_email'] : $entry->applicant_email,
        ]);

        if ($entry->isDirty()) {
            $entry->save();
        }

        return $entry->fresh();
    }
}
