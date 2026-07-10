<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
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
        private readonly HospitalVideoUpdateHistoryRecordAction $historyRecordAction,
        private readonly HospitalVideoSyncHashtagsForStaffAction $syncHashtagsAction,
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
            $this->syncHashtagsAction->execute($video, $normalized['hashtag_ids'] ?? [], $normalized['hashtag_names'] ?? []);

            $video = $video->fresh($this->detailRelations());

            $this->historyRecordAction->recordCreated($video);

            return $video;
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
}
