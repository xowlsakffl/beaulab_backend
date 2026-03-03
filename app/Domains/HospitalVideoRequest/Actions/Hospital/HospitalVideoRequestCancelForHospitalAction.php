<?php

namespace App\Domains\HospitalVideoRequest\Actions\Hospital;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use App\Domains\HospitalVideoRequest\Dto\Hospital\HospitalVideoRequestForHospitalDetailDto;
use App\Domains\HospitalVideoRequest\Queries\Hospital\HospitalVideoRequestCancelForHospitalQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoRequestCancelForHospitalAction
{
    public function __construct(private readonly HospitalVideoRequestCancelForHospitalQuery $query) {}

    public function execute(HospitalVideoRequest $videoRequest, array $payload): array
    {
        unset($payload);

        Gate::authorize('cancel', $videoRequest);

        if (! $videoRequest->isApplying()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '신청중 상태일 때만 취소할 수 있습니다.');
        }

        $videoRequest = DB::transaction(fn () => $this->query->cancel($videoRequest));

        return [
            'video_request' => HospitalVideoRequestForHospitalDetailDto::fromModel($videoRequest)->toArray(),
        ];
    }
}
