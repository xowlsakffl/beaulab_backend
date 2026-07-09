<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoUpdateForStaffQuery
{
    public function update(HospitalVideo $video, array $payload): HospitalVideo
    {
        $fields = [
            'hospital_id',
            'doctor_id',
            'manager_staff_id',
            'title',
            'description',
            'external_video_url',
            'view_count',
            'like_count',
        ];

        $data = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = in_array($field, ['view_count', 'like_count'], true)
                    ? (int) ($payload[$field] ?? 0)
                    : $payload[$field];
            }
        }

        $video->fill($data);

        if ($video->isDirty()) {
            $video->save();
        }

        return $video->fresh();
    }
}
