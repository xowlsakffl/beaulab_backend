<?php

namespace App\Domains\VideoRequest\Actions\Partner;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Common\Models\Media\Media;
use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestUpdateForPartnerQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class VideoRequestUpdateForPartnerAction
{
    public function __construct(
        private readonly VideoRequestUpdateForPartnerQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(VideoRequest $videoRequest, array $payload): array
    {
        Gate::authorize('update', $videoRequest);

        $videoRequest = DB::transaction(function () use ($videoRequest, $payload) {
            $updated = $this->query->update($videoRequest, $payload);
            $this->replaceMedia($updated, $payload);

            return $updated->fresh();
        });

        return [
            'video_request' => VideoRequestForPartnerDetailDto::fromModel($videoRequest->load(['sourceVideo', 'sourceThumbnail']))->toArray(),
        ];
    }

    private function replaceMedia(VideoRequest $videoRequest, array $payload): void
    {
        if (($payload['source_video_file'] ?? null) instanceof UploadedFile) {
            $this->deleteCollectionMedia($videoRequest, 'source_video_file');
            $this->mediaAttachAction->attachVideoRequestSourceVideo($videoRequest, $payload['source_video_file'], 'video-request');
        }

        if (($payload['source_thumbnail_file'] ?? null) instanceof UploadedFile) {
            $this->deleteCollectionMedia($videoRequest, 'source_thumbnail_file');
            $this->mediaAttachAction->attachVideoRequestSourceThumbnail($videoRequest, $payload['source_thumbnail_file'], 'video-request');
        }
    }

    private function deleteCollectionMedia(VideoRequest $videoRequest, string $collection): void
    {
        Media::query()->for($videoRequest)->collection($collection)->get()->each(function (Media $media): void {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        });
    }
}
