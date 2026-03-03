<?php

namespace App\Domains\HospitalVideoRequest\Actions\Staff;

use App\Domains\HospitalVideoRequest\Dto\Staff\HospitalVideoRequestForStaffDetailDto;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoRequestGetForStaffAction
{
    public function execute(HospitalVideoRequest $videoRequest): array
    {
        Gate::authorize('view', $videoRequest);

        return [
            'video_request' => HospitalVideoRequestForStaffDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
