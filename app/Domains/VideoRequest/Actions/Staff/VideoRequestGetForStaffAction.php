<?php

namespace App\Domains\VideoRequest\Actions\Staff;

use App\Domains\VideoRequest\Dto\Staff\VideoRequestForStaffDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use Illuminate\Support\Facades\Gate;

final class VideoRequestGetForStaffAction
{
    public function execute(VideoRequest $videoRequest): array
    {
        Gate::authorize('view', $videoRequest);

        return [
            'video_request' => VideoRequestForStaffDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
