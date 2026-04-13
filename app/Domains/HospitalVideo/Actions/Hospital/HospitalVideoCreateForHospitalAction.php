<?php

namespace App\Domains\HospitalVideo\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalVideo\Dto\Hospital\HospitalVideoForHospitalDetailDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Hospital\HospitalVideoCreateForHospitalQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalVideoCreateForHospitalAction 역할 정의.
 * 병원 동영상 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalVideoCreateForHospitalAction
{
    public function __construct(
        private readonly HospitalVideoCreateForHospitalQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(AccountHospital $actor, array $payload): array
    {
        Gate::authorize('create', HospitalVideo::class);

        $normalized = $this->normalizePayload($actor, $payload);

        $video = DB::transaction(function () use ($normalized) {
            $video = $this->query->create($normalized);

            $this->mediaAttachAction->attachOne(
                $video,
                $normalized['thumbnail_file'],
                'thumbnail_file',
                'hospital-video',
                'thumbnail',
            );

            $this->mediaAttachAction->attachOne(
                $video,
                $normalized['video_file'],
                'video_file',
                'hospital-video',
                'video',
            );

            $this->syncCategories($video, $normalized['category_ids']);

            return $video->fresh(['thumbnailMedia', 'videoFileMedia', 'categories']);
        });

        return [
            'video' => HospitalVideoForHospitalDetailDto::fromModel($video)->toArray(),
        ];
    }

    private function normalizePayload(AccountHospital $actor, array $payload): array
    {
        if ((int) $actor->hospital_id <= 0) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '소속 병원 정보가 없는 계정입니다.');
        }

        if (! empty($payload['doctor_id'])) {
            $doctor = HospitalDoctor::query()->find($payload['doctor_id']);

            if (! $doctor || (int) $doctor->hospital_id !== (int) $actor->hospital_id) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '요청하신 병원에 소속된 의사가 아닙니다.');
            }
        }

        $payload['hospital_id'] = (int) $actor->hospital_id;
        $payload['submitted_by_account_id'] = (int) $actor->id;
        $payload['is_usage_consented'] = true;

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

        if ($syncPayload === []) {
            return;
        }

        $video->categories()->sync($syncPayload);
    }
}
