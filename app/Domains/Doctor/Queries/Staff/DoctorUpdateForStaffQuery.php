<?php

namespace App\Domains\Doctor\Queries\Staff;

use App\Domains\Doctor\Models\Doctor;

final class DoctorUpdateForStaffQuery
{
    public function update(Doctor $doctor, array $payload): Doctor
    {
        $doctor->fill([
            'sort_order' => array_key_exists('sort_order', $payload) ? $payload['sort_order'] : $doctor->sort_order,
            'name' => array_key_exists('name', $payload) ? $payload['name'] : $doctor->name,
            'gender' => array_key_exists('gender', $payload) ? $payload['gender'] : $doctor->gender,
            'position' => array_key_exists('position', $payload) ? $payload['position'] : $doctor->position,
            'career_started_at' => array_key_exists('career_started_at', $payload) ? $payload['career_started_at'] : $doctor->career_started_at,
            'license_number' => array_key_exists('license_number', $payload) ? $payload['license_number'] : $doctor->license_number,
            'is_specialist' => array_key_exists('is_specialist', $payload) ? (bool) $payload['is_specialist'] : $doctor->is_specialist,
            'educations' => array_key_exists('educations', $payload) ? $payload['educations'] : $doctor->educations,
            'careers' => array_key_exists('careers', $payload) ? $payload['careers'] : $doctor->careers,
            'etc_contents' => array_key_exists('etc_contents', $payload) ? $payload['etc_contents'] : $doctor->etc_contents,
            'status' => array_key_exists('status', $payload) ? $payload['status'] : $doctor->status,
            'allow_status' => array_key_exists('allow_status', $payload) ? $payload['allow_status'] : $doctor->allow_status,
        ]);

        if ($doctor->isDirty()) {
            $doctor->save();
        }

        return $doctor->fresh();
    }
}
