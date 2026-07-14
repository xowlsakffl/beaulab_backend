<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Support\HospitalEventAdSalesDeadline;
use Illuminate\Support\Carbon;

final class HospitalEventAdPeriodResolver
{
    public function __construct(
        private readonly HospitalEventAdSalesDeadline $salesDeadline,
    ) {}

    /**
     * @return array{start_at: Carbon, end_at: Carbon}
     */
    public function resolve(string $startDate, string $placement): array
    {
        $startAt = Carbon::createFromFormat('Y-m-d H:i:s', "{$startDate} 11:00:00", config('app.timezone'));
        $startDayOfWeek = HospitalEventAd::startDayOfWeek($placement);

        if (! $startAt instanceof Carbon || $startAt->dayOfWeek !== $startDayOfWeek) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, sprintf(
                '광고 노출 시작일은 %s만 선택할 수 있습니다.',
                HospitalEventAd::startDayLabel($placement),
            ));
        }

        if ($startAt->copy()->startOfDay()->lessThanOrEqualTo(now()->startOfDay())) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '광고 노출 시작일은 오늘 이후 날짜만 선택할 수 있습니다.');
        }

        if ($this->salesDeadline->isClosed($startAt)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '광고 게시일 2영업일 전까지만 신청할 수 있습니다.');
        }

        return [
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addWeek()->subSecond(),
        ];
    }
}
