<?php

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\HospitalEvent\Models\HospitalEvent;

final class HospitalEventCreateForStaffQuery
{
    public function create(array $data): HospitalEvent
    {
        return HospitalEvent::query()->create($this->persistable($data));
    }

    /**
     * @return array<string, mixed>
     */
    private function persistable(array $data): array
    {
        return [
            'hospital_id' => $data['hospital_id'],
            'event_type' => $data['event_type'],
            'name' => $data['name'],
            'description' => $data['description'],
            'is_event_period_unlimited' => $data['is_event_period_unlimited'],
            'event_start_at' => $data['event_start_at'] ?? null,
            'event_end_at' => $data['event_end_at'] ?? null,
            'normal_price' => $data['normal_price'],
            'event_price' => $data['event_price'],
            'is_vat_included' => $data['is_vat_included'],
            'discount_rate' => $data['discount_rate'],
            'base_consultation_price' => $data['base_consultation_price'],
            'consultation_price' => $data['consultation_price'],
            'has_options' => $data['has_options'],
            'procedure_targets' => $data['procedure_targets'] ?? null,
            'procedure_benefits' => $data['procedure_benefits'] ?? null,
            'side_effect_notice' => $data['side_effect_notice'] ?? null,
            'allow_status' => $data['allow_status'] ?? HospitalEvent::ALLOW_PENDING,
            'status' => $data['status'] ?? HospitalEvent::STATUS_INACTIVE,
        ];
    }
}
