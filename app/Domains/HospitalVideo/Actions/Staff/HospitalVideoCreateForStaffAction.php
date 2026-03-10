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
                $thumbnail = $this->mediaAttachAction->attachOne(
                    $video,
                    $normalized['thumbnail_file'],
                    'thumbnail_file',
                    'hospital-video',
                    'thumbnail',
                );

                if ($thumbnail) {
                    $video = $this->query->updateThumbnailMediaId($video, $thumbnail->id);
                }
            }

            return $video->fresh(['thumbnailMedia']);
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
                throw new CustomException(ErrorCode::INVALID_REQUEST, 'Doctor does not belong to the selected hospital.');
            }
        }

        if (($payload['is_publish_period_unlimited'] ?? false) === true) {
            $payload['publish_start_at'] = null;
            $payload['publish_end_at'] = null;
        }

        return $payload;
    }
}
