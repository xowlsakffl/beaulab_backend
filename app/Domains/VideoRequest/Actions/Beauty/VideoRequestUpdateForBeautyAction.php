<?php

namespace App\Domains\VideoRequest\Actions\Beauty;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Doctor\Models\Doctor;
use App\Domains\Expert\Models\Expert;
use App\Domains\Beauty\Models\AccountBeauty;
use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDetailDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestUpdateForPartnerQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class VideoRequestUpdateForBeautyAction
{
    public function __construct(
        private readonly VideoRequestUpdateForPartnerQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(VideoRequest $videoRequest, array $payload): array
    {
        Gate::authorize('update', $videoRequest);

        /** @var AccountBeauty $partner */
        $partner = Auth::user();

        if (! $videoRequest->isPending()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, 'PENDING 상태일 때만 수정할 수 있습니다.');
        }

        $normalized = $this->normalizePayloadByPartner($partner, $payload);

        $videoRequest = DB::transaction(function () use ($videoRequest, $normalized) {
            $updated = $this->query->update($videoRequest, $normalized);

            if (! empty($normalized['source_video_file'])) {
                $this->mediaAttachAction->attachVideoRequestSourceVideo($updated, $normalized['source_video_file'], 'video-request');
            }

            if (! empty($normalized['source_thumbnail_file'])) {
                $this->mediaAttachAction->attachVideoRequestSourceThumbnail($updated, $normalized['source_thumbnail_file'], 'video-request');
            }

            return $updated->fresh(['sourceVideo', 'sourceThumbnail']);
        });

        return [
            'video_request' => VideoRequestForPartnerDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }

    private function normalizePayloadByPartner(AccountBeauty $partner, array $payload): array
    {
        if ($partner->isHospital()) {
            if (! $partner->hospital_id) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '병원 소속 파트너 정보가 올바르지 않습니다.');
            }

            $payload['hospital_id'] = $partner->hospital_id;
            $payload['beauty_id'] = null;

            if (array_key_exists('expert_id', $payload) && ! empty($payload['expert_id'])) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '병원 파트너는 뷰티전문가를 지정할 수 없습니다.');
            }

            if (array_key_exists('doctor_id', $payload) && ! empty($payload['doctor_id'])) {
                $doctor = Doctor::query()->find($payload['doctor_id']);
                if (! $doctor || (int) $doctor->hospital_id !== (int) $partner->hospital_id) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '본인 소속 병원의 의사만 지정할 수 있습니다.');
                }
            }
        }


        if (! $partner->isHospital() && ! $partner->isBeauty()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 파트너 타입입니다.');
        }

        if ($partner->isBeauty()) {
            if (! $partner->beauty_id) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '뷰티 소속 파트너 정보가 올바르지 않습니다.');
            }

            $payload['beauty_id'] = $partner->beauty_id;
            $payload['hospital_id'] = null;

            if (array_key_exists('doctor_id', $payload) && ! empty($payload['doctor_id'])) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '뷰티 파트너는 의사를 지정할 수 없습니다.');
            }

            if (array_key_exists('expert_id', $payload) && ! empty($payload['expert_id'])) {
                $expert = Expert::query()->find($payload['expert_id']);
                if (! $expert || (int) $expert->beauty_id !== (int) $partner->beauty_id) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '본인 소속 뷰티의 전문가만 지정할 수 있습니다.');
                }
            }
        }

        return $payload;
    }
}
