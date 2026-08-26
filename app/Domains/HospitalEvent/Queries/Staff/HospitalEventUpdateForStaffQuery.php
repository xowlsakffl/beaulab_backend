<?php

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\HospitalEvent\Models\HospitalEvent;

final class HospitalEventUpdateForStaffQuery
{
    public function update(HospitalEvent $event, array $data): HospitalEvent
    {
        $event->fill($this->persistable($data));
        $event->save();

        return $event;
    }

    /**
     * @return array<string, mixed>
     */
    private function persistable(array $data): array
    {
        $fields = [
            'hospital_id',
            'manager_staff_id',
            'event_type',
            'is_male_targeted',
            'name',
            'description',
            'is_event_period_unlimited',
            'event_start_at',
            'event_end_at',
            'normal_price',
            'event_price',
            'is_vat_included',
            'discount_rate',
            'base_consultation_price',
            'consultation_price',
            'has_options',
            'procedure_targets',
            'procedure_benefits',
            'side_effect_notice',
            'allow_status',
            'hospital_status',
            'admin_status',
        ];

        $out = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $out[$field] = $data[$field];
            }
        }

        return $out;
    }
}
