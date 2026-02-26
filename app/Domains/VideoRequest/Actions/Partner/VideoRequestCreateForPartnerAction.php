<?php

namespace App\Domains\VideoRequest\Actions\Partner;

use App\Domains\Partner\Models\AccountPartner;
use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestCreateForPartnerQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class VideoRequestCreateForPartnerAction
{
    public function __construct(private readonly VideoRequestCreateForPartnerQuery $query) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', VideoRequest::class);

        /** @var AccountPartner $actor */
        $actor = Auth::user();

        $videoRequest = DB::transaction(fn () => $this->query->create($payload, $actor));

        return [
            'video_request' => VideoRequestForPartnerDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
