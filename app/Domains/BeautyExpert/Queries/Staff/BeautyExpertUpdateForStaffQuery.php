<?php

namespace App\Domains\BeautyExpert\Queries\Staff;

use App\Domains\BeautyExpert\Models\BeautyExpert;

final class BeautyExpertUpdateForStaffQuery
{
    public function update(BeautyExpert $expert, array $payload): BeautyExpert
    {
        $expert->fill([
            'sort_order' => array_key_exists('sort_order', $payload) ? $payload['sort_order'] : $expert->sort_order,
            'name' => array_key_exists('name', $payload) ? $payload['name'] : $expert->name,
            'gender' => array_key_exists('gender', $payload) ? $payload['gender'] : $expert->gender,
            'position' => array_key_exists('position', $payload) ? $payload['position'] : $expert->position,
            'career_started_at' => array_key_exists('career_started_at', $payload) ? $payload['career_started_at'] : $expert->career_started_at,
            'educations' => array_key_exists('educations', $payload) ? $payload['educations'] : $expert->educations,
            'careers' => array_key_exists('careers', $payload) ? $payload['careers'] : $expert->careers,
            'etc_contents' => array_key_exists('etc_contents', $payload) ? $payload['etc_contents'] : $expert->etc_contents,
            'status' => array_key_exists('status', $payload) ? $payload['status'] : $expert->status,
            'allow_status' => array_key_exists('allow_status', $payload) ? $payload['allow_status'] : $expert->allow_status,
        ]);

        if ($expert->isDirty()) {
            $expert->save();
        }

        return $expert->fresh();
    }
}
