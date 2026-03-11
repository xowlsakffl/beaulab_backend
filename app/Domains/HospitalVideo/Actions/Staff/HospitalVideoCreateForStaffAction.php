<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalVideo\Dto\Staff\HospitalVideoForStaffDetailDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoCreateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoCreateForStaffAction
{
    public function __construct(
        private readonly HospitalVideoCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', HospitalVideo::class);

        $normalized = $this->normalizePayload($payload);

        $video = DB::transaction(function () use ($normalized) {
            $video = $this->query->create($normalized);

            if (! empty($normalized['thumbnail_file'])) {
                $this->mediaAttachAction->attachOne(
                    $video,
                    $normalized['thumbnail_file'],
                    'thumbnail_file',
                    'hospital-video',
                    'thumbnail',
                );
            }

            $this->syncCategories($video, $normalized['category_ids'] ?? []);

            return $video->fresh(['thumbnailMedia', 'categories']);
        });

        return [
            'video' => HospitalVideoForStaffDetailDto::fromModel($video)->toArray(),
        ];
    }

    private function normalizePayload(array $payload): array
    {
        if (! empty($payload['doctor_id'])) {
            $doctor = HospitalDoctor::query()->find($payload['doctor_id']);

            if (! $doctor || (int) $doctor->hospital_id !== (int) $payload['hospital_id']) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '요청하신 병원에 소속된 의사가 아닙니다.');
            }
        }

        if (($payload['is_publish_period_unlimited'] ?? false) === true) {
            $payload['publish_start_at'] = null;
            $payload['publish_end_at'] = null;
        }

        return $payload;
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(HospitalVideo $video, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        $syncPayload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        if ($syncPayload === []) {
            return;
        }

        $video->categories()->sync($syncPayload);
    }
}
