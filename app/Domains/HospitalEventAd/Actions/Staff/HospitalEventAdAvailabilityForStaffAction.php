<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdAvailabilityForStaffQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdAvailabilityForStaffAction
{
    public function __construct(
        private readonly HospitalEventAdAvailabilityForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('create', HospitalEventAd::class);

        $placement = (string) $filters['placement'];
        $categoryId = $filters['category_id'] ?? null;
        $month = Carbon::createFromFormat('Y-m', (string) $filters['month'], config('app.timezone'))->startOfMonth();

        $this->assertVisibleMonth($month);

        return $this->query->get($placement, $categoryId !== null ? (int) $categoryId : null, $month);
    }

    private function assertVisibleMonth(Carbon $month): void
    {
        $currentMonth = now()->startOfMonth();
        $nextMonth = now()->addMonthNoOverflow()->startOfMonth();

        if (! $month->equalTo($currentMonth) && ! $month->equalTo($nextMonth)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '광고 구좌는 현재월과 다음달만 조회할 수 있습니다.');
        }
    }
}
