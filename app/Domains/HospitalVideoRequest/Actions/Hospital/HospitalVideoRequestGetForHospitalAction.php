<?php

namespace App\Domains\HospitalVideoRequest\Actions\Hospital;

use App\Domains\HospitalVideoRequest\Dto\Hospital\HospitalVideoRequestForHospitalDetailDto;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoRequestGetForHospitalAction
{
    public function execute(HospitalVideoRequest $videoRequest): array
    {
        Gate::authorize('view', $videoRequest);

        return [
            'video_request' => HospitalVideoRequestForHospitalDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
