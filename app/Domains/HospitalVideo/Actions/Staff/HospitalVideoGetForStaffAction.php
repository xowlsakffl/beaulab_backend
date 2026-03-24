<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\HospitalVideo\Dto\Staff\HospitalVideoForStaffDetailDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoGetForStaffAction
{
    public function execute(HospitalVideo $video, string $ability = 'view'): array
    {
        Gate::authorize($ability, $video);

        $relations = [
            'hospital',
            'doctor',
            'thumbnailMedia',
            'videoFileMedia',
            'categories',
        ];

        if ($ability === 'update') {
            $relations[] = 'hospital.businessRegistration';
        }

        return [
            'video' => HospitalVideoForStaffDetailDto::fromModel($video->load($relations))->toArray(),
        ];
    }
}
