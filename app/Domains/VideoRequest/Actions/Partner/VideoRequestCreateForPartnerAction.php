<?php

namespace App\Domains\VideoRequest\Actions\Partner;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Partner\Models\AccountPartner;
use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestCreateForPartnerQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class VideoRequestCreateForPartnerAction
{
    public function __construct(
        private readonly VideoRequestCreateForPartnerQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', VideoRequest::class);

        /** @var AccountPartner $actor */
        $actor = Auth::user();

        $videoRequest = DB::transaction(function () use ($payload, $actor) {
            $videoRequest = $this->query->create($payload, $actor);

            $this->mediaAttachAction->attachVideoRequestSourceVideo($videoRequest, $payload['source_video_file'], 'video-request');
            $this->mediaAttachAction->attachVideoRequestSourceThumbnail($videoRequest, $payload['source_thumbnail_file'], 'video-request');

            return $videoRequest->fresh();
        });

        return [
            'video_request' => VideoRequestForPartnerDetailDto::fromModel($videoRequest->load(['sourceVideo', 'sourceThumbnail']))->toArray(),
        ];
    }
}
