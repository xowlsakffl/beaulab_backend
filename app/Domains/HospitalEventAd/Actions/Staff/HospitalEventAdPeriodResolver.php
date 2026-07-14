<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class HospitalEventAdPeriodResolver
{
    /**
     * @return array{start_at: Carbon, end_at: Carbon}
     */
    public function resolve(string $startDate): array
    {
        $startAt = Carbon::createFromFormat('Y-m-d H:i:s', "{$startDate} 11:00:00", config('app.timezone'));

        if (! $startAt instanceof Carbon || $startAt->dayOfWeek !== CarbonInterface::TUESDAY) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '광고 노출 시작일은 화요일만 선택할 수 있습니다.');
        }

        if ($startAt->copy()->startOfDay()->lessThan(now()->startOfDay())) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '광고 노출 시작일은 오늘 이후 날짜만 선택할 수 있습니다.');
        }

        return [
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addWeek()->subSecond(),
        ];
    }
}
