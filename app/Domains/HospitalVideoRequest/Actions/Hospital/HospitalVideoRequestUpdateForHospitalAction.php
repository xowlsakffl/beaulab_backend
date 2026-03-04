<?php

namespace App\Domains\HospitalVideoRequest\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalVideoRequest\Dto\Hospital\HospitalVideoRequestForHospitalDetailDto;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use App\Domains\HospitalVideoRequest\Queries\Hospital\HospitalVideoRequestUpdateForHospitalQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoRequestUpdateForHospitalAction
{
    public function __construct(
        private readonly HospitalVideoRequestUpdateForHospitalQuery $query,
        private readonly MediaAttachAction                          $mediaAttachAction,
    ) {}

    public function execute(HospitalVideoRequest $videoRequest, array $payload): array
    {
        Gate::authorize('update', $videoRequest);

        /** @var AccountHospital $account */
        $account = Auth::user();

        if (! $videoRequest->isApplying()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '신청중 상태일 때만 수정할 수 있습니다.');
        }

        $normalized = $this->normalizePayloadByAccountHospital($account, $payload);

        $videoRequest = DB::transaction(function () use ($videoRequest, $normalized) {
            $updated = $this->query->update($videoRequest, $normalized);

            if (! empty($normalized['source_video_file'])) {
                $this->mediaAttachAction->attachOne($updated, $normalized['source_video_file'], 'source_video_file', 'video-request', 'source-video');
            }

            if (! empty($normalized['source_thumbnail_file'])) {
                $this->mediaAttachAction->attachOne($updated, $normalized['source_thumbnail_file'], 'source_thumbnail_file', 'video-request', 'source-thumbnail');
            }

            return $updated->fresh(['sourceVideo', 'sourceThumbnail']);
        });

        return [
            'video_request' => HospitalVideoRequestForHospitalDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }

    private function normalizePayloadByAccountHospital(AccountHospital $accountHospital, array $payload): array
    {
        if (! $accountHospital->hospital_id) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '소속 병원 정보가 올바르지 않습니다.');
        }

        $payload['hospital_id'] = $accountHospital->hospital_id;

        if (array_key_exists('doctor_id', $payload) && ! empty($payload['doctor_id'])) {
            $doctor = HospitalDoctor::query()->find($payload['doctor_id']);
            if (! $doctor || (int) $doctor->hospital_id !== (int) $accountHospital->hospital_id) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '본인 소속 병원의 의사만 지정할 수 있습니다.');
            }
        }

        return $payload;
    }
}