<?php

namespace App\Domains\VideoRequest\Actions\Partner;

use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use Illuminate\Support\Facades\Gate;

final class VideoRequestGetForPartnerAction
{
    public function execute(VideoRequest $videoRequest): array
    {
        Gate::authorize('view', $videoRequest);

        return [
            'video_request' => VideoRequestForPartnerDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
