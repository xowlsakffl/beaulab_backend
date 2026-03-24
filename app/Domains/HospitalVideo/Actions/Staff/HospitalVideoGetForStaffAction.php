<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\HospitalVideo\Dto\Staff\HospitalVideoForStaffDetailDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoGetForStaffAction
{
    public function execute(HospitalVideo $video): array
    {
        Gate::authorize('view', $video);

        return [
            'video' => HospitalVideoForStaffDetailDto::fromModel($video->load([
                'thumbnailMedia',
                'videoFileMedia',
                'categories',
            ]))->toArray(),
        ];
    }
}
