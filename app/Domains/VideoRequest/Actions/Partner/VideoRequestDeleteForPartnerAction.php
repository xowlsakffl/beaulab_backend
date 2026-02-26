<?php

namespace App\Domains\VideoRequest\Actions\Partner;

use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestDeleteForPartnerQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class VideoRequestDeleteForPartnerAction
{
    public function __construct(private readonly VideoRequestDeleteForPartnerQuery $query) {}

    public function execute(VideoRequest $videoRequest): array
    {
        Gate::authorize('delete', $videoRequest);

        return DB::transaction(function () use ($videoRequest) {
            $this->query->softDelete($videoRequest);
            $videoRequest->refresh();

            return [
                'deleted_id' => (int) $videoRequest->id,
                'deleted_at' => optional($videoRequest->deleted_at)?->toISOString(),
            ];
        });
    }
}
