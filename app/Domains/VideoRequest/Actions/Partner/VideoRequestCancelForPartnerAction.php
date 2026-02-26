<?php

namespace App\Domains\VideoRequest\Actions\Partner;

use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestCancelForPartnerQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class VideoRequestCancelForPartnerAction
{
    public function __construct(private readonly VideoRequestCancelForPartnerQuery $query) {}

    public function execute(VideoRequest $videoRequest, array $payload): array
    {
        Gate::authorize('cancel', $videoRequest);

        $videoRequest = DB::transaction(fn () => $this->query->cancel($videoRequest, $payload));

        return [
            'video_request' => VideoRequestForPartnerDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
