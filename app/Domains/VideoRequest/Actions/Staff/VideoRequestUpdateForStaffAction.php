<?php

namespace App\Domains\VideoRequest\Actions\Staff;

use App\Domains\VideoRequest\Dto\Staff\VideoRequestForStaffDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Staff\VideoRequestUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class VideoRequestUpdateForStaffAction
{
    public function __construct(private readonly VideoRequestUpdateForStaffQuery $query) {}

    public function execute(VideoRequest $videoRequest, array $payload): array
    {
        Gate::authorize('update', $videoRequest);

        $videoRequest = DB::transaction(fn () => $this->query->update($videoRequest, $payload));

        return [
            'video_request' => VideoRequestForStaffDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
