<?php

namespace App\Domains\HospitalVideoRequest\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\Expert\Models\BeautyExpert;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\HospitalVideoRequest\Dto\Hospital\HospitalVideoRequestForHospitalDetailDto;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use App\Domains\HospitalVideoRequest\Queries\Hospital\HospitalVideoRequestCreateForHospitalQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoRequestCreateForHospitalAction
{
    public function __construct(
        private readonly HospitalVideoRequestCreateForHospitalQuery $query,
        private readonly MediaAttachAction                          $mediaAttachAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', HospitalVideoRequest::class);

        /** @var AccountHospital $account */
        $account = Auth::user();

        $normalized = $this->normalizePayloadByAccountHospital($account, $payload);

        $videoRequest = DB::transaction(function () use ($normalized) {
            $videoRequest = $this->query->create($normalized);

            $this->mediaAttachAction->attachVideoRequestSourceVideo($videoRequest, $normalized['source_video_file'], 'video-request');

            if (! empty($normalized['source_thumbnail_file'])) {
                $this->mediaAttachAction->attachVideoRequestSourceThumbnail($videoRequest, $normalized['source_thumbnail_file'], 'video-request');
            }

            return $videoRequest->fresh(['sourceVideo', 'sourceThumbnail']);
        });

        return [
            'video_request' => HospitalVideoRequestForHospitalDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }

    private function normalizePayloadByAccountHospital(AccountHospital $accountHospital, array $payload): array
    {
        $payload['submitted_by_account_id'] = $accountHospital->id;


        if (! $accountHospital->hospital_id) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '소속 병원 정보가 올바르지 않습니다.');
        }

        $payload['hospital_id'] = $accountHospital->hospital_id;

        if (! empty($payload['doctor_id'])) {
            $doctor = HospitalDoctor::query()->find($payload['doctor_id']);
            if (! $doctor || (int) $doctor->hospital_id !== (int) $accountHospital->hospital_id) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '본인 소속 병원의 의사만 지정할 수 있습니다.');
            }
        }

        return $payload;
    }
}
