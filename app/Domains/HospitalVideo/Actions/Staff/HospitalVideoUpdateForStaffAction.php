<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalVideo\Dto\Staff\HospitalVideoForStaffDetailDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalVideoUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(HospitalVideo $video, array $payload): array
    {
        Gate::authorize('update', $video);

        $normalized = $this->normalizePayload($video, $payload);

        $video = DB::transaction(function () use ($video, $normalized) {
            $updated = $this->query->update($video, $normalized);

            if (array_key_exists('thumbnail_file', $normalized) && $normalized['thumbnail_file'] instanceof UploadedFile) {
                $this->mediaAttachAction->deleteCollectionMedia($updated, 'thumbnail_file');
                $this->mediaAttachAction->attachOne(
                    $updated,
                    $normalized['thumbnail_file'],
                    'thumbnail_file',
                    'hospital-video',
                    'thumbnail',
                );
            } elseif (array_key_exists('existing_thumbnail_file_id', $normalized) && empty($normalized['existing_thumbnail_file_id'])) {
                $this->mediaAttachAction->deleteCollectionMedia($updated, 'thumbnail_file');
            }

            if (($normalized['remove_video_file'] ?? false) === true) {
                $this->mediaAttachAction->deleteCollectionMedia($updated, 'video_file');
            }

            if (array_key_exists('category_ids', $normalized) && is_array($normalized['category_ids'])) {
                $this->syncCategories($updated, $normalized['category_ids']);
            }

            return $updated->fresh([
                'hospital',
                'doctor',
                'thumbnailMedia',
                'videoFileMedia',
                'categories',
            ]);
        });

        return [
            'video' => HospitalVideoForStaffDetailDto::fromModel($video)->toArray(),
        ];
    }

    private function normalizePayload(HospitalVideo $video, array $payload): array
    {
        $targetHospitalId = array_key_exists('hospital_id', $payload)
            ? (int) $payload['hospital_id']
            : (int) $video->hospital_id;

        if (array_key_exists('doctor_id', $payload) && ! empty($payload['doctor_id'])) {
            $doctor = HospitalDoctor::query()->find($payload['doctor_id']);

            if (! $doctor || (int) $doctor->hospital_id !== $targetHospitalId) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '요청하신 병원에 소속된 의사가 아닙니다');
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
        $syncPayload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        $video->categories()->sync($syncPayload);
    }
}
