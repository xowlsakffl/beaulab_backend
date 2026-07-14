<?php

namespace App\Domains\HospitalEventAd\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class HospitalEventAdSalesDeadline
{
    public const BUSINESS_DAYS_BEFORE_START = 2;

    public function isClosed(CarbonInterface $startAt, ?CarbonInterface $now = null): bool
    {
        return $this->now($now)->greaterThanOrEqualTo($this->closedFrom($startAt));
    }

    public function deadlineAt(CarbonInterface $startAt): Carbon
    {
        return $this->closedFrom($startAt)->subSecond();
    }

    public function closedFrom(CarbonInterface $startAt): Carbon
    {
        $date = Carbon::instance($startAt)->copy()->startOfDay();
        $remainingBusinessDays = self::BUSINESS_DAYS_BEFORE_START;

        while ($remainingBusinessDays > 0) {
            $date->subDay();

            if ($date->isWeekday()) {
                $remainingBusinessDays--;
            }
        }

        return $date;
    }

    private function now(?CarbonInterface $now = null): Carbon
    {
        return ($now ? Carbon::instance($now) : now())->copy();
    }
}
