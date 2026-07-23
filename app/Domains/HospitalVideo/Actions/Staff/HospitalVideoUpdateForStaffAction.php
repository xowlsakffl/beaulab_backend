<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
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
        private readonly HospitalVideoUpdateHistoryRecordAction $historyRecordAction,
        private readonly HospitalVideoSyncHashtagsForStaffAction $syncHashtagsAction,
    ) {}

    public function execute(HospitalVideo $video, array $payload): array
    {
        Gate::authorize('update', $video);

        $normalized = $this->normalizePayload($video, $payload);

        $video = DB::transaction(function () use ($video, $normalized) {
            $before = $this->historyRecordAction->capture($video);
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

            if (array_key_exists('category_ids', $normalized) && is_array($normalized['category_ids'])) {
                $this->syncCategories($updated, $normalized['category_ids']);
            }

            if (
                (array_key_exists('hashtag_ids', $normalized) && is_array($normalized['hashtag_ids']))
                || (array_key_exists('hashtag_names', $normalized) && is_array($normalized['hashtag_names']))
            ) {
                $this->syncHashtagsAction->execute($updated, $normalized['hashtag_ids'] ?? [], $normalized['hashtag_names'] ?? []);
            }

            $updated = $updated->fresh($this->detailRelations());

            $this->historyRecordAction->recordUpdated($updated, $before);

            return $updated;
        });

        if ($this->shouldForgetSummary($normalized)) {
            StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_VIDEO);
        }

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
                throw new CustomException(ErrorCode::INVALID_REQUEST, '요청하신 병의원에 소속된 의료진이 아닙니다.');
            }
        }

        return $payload;
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'hospital',
            'hospital.businessRegistration',
            'doctor',
            'managerStaff',
            'thumbnailMedia',
            'contentReportState',
            'categories',
            'hashtags',
            'operationHistories.actor',
        ];
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     */
    private function syncCategories(HospitalVideo $video, array $categoryIds): void
    {
        $syncIds = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->all();

        $video->categories()->sync($syncIds);
    }

    private function shouldForgetSummary(array $payload): bool
    {
        return array_key_exists('hospital_status', $payload)
            || array_key_exists('admin_status', $payload);
    }
}
