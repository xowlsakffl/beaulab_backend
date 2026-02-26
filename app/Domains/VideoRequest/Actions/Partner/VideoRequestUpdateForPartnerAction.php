<?php

namespace App\Domains\VideoRequest\Actions\Partner;

use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestUpdateForPartnerQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class VideoRequestUpdateForPartnerAction
{
    public function __construct(private readonly VideoRequestUpdateForPartnerQuery $query) {}

    public function execute(VideoRequest $videoRequest, array $payload): array
    {
        Gate::authorize('update', $videoRequest);

        $videoRequest = DB::transaction(fn () => $this->query->update($videoRequest, $payload));

        return [
            'video_request' => VideoRequestForPartnerDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
