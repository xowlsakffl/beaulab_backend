<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Models\HospitalEventAdPricingVersion;
use App\Domains\HospitalEventAd\Queries\HospitalEventAdPricingQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdPricingManageAction
{
    public function __construct(private readonly HospitalEventAdPricingQuery $query) {}

    public function show(string $group): array
    {
        Gate::authorize(AccessPermissions::BEAULAB_AD_PRICING_MANAGE);
        $now = CarbonImmutable::now();
        $scope = DB::table('hospital_event_ad_pricing_groups')->where('group_key', $group)->first();
        $current = $this->query->current($group, $now);
        $pending = $this->query->pending($group, $now);

        return [
            'group' => $group,
            'lock_version' => (int) ($scope?->lock_version ?? 0),
            'current_version_id' => $current?->id,
            'server_time' => $now->toIso8601String(),
            'rows' => $this->rows($group, $current),
            'pending' => $pending ? [
                'id' => $pending->id,
                'effective_at' => $pending->effective_at->toIso8601String(),
                'rows' => $this->rows($group, $pending),
            ] : null,
        ];
    }

    public function save(array $input, bool $cancel = false): array
    {
        Gate::authorize(AccessPermissions::BEAULAB_AD_PRICING_MANAGE);
        $group = $input['group'];
        DB::transaction(function () use ($group, $input, $cancel) {
            DB::table('hospital_event_ad_pricing_groups')->insertOrIgnore([
                'group_key' => $group, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $scope = DB::table('hospital_event_ad_pricing_groups')->where('group_key', $group)->lockForUpdate()->first();
            $now = CarbonImmutable::now();
            $current = $this->query->current($group, $now);
            if ((int) $scope->lock_version !== (int) $input['lock_version']
                || (int) ($current?->id ?? 0) !== (int) ($input['current_version_id'] ?? 0)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '가격 설정이 변경되었거나 예약이 적용되었습니다. 화면을 새로 연 뒤 수정해 주세요.');
            }
            $pending = $this->query->pending($group, $now);
            if ($cancel) {
                if (! $pending || (int) $pending->id !== (int) $input['pending_id']) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '예약이 이미 적용되었거나 취소되었습니다.');
                }
                $pending->update(['cancelled_at' => $now, 'cancelled_by' => auth()->id()]);
            } else {
                if ($pending) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '기존 적용 예약을 취소한 뒤 수정해 주세요.');
                }
                $prices = $this->prices($group, $input['prices']);
                $at = ! empty($input['effective_at'])
                    ? CarbonImmutable::parse($input['effective_at'])->setTimezone(config('app.timezone')) : $now;
                if (! empty($input['effective_at']) && $at->lessThanOrEqualTo($now)) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '예약 시각은 현재 시각 이후로 선택해 주세요.', ['errors' => ['effective_at' => ['예약 시각은 현재 시각 이후로 선택해 주세요.']]]);
                }
                HospitalEventAdPricingVersion::create([
                    'group_id' => $scope->id, 'prices' => $prices, 'effective_at' => $at, 'created_by' => auth()->id(),
                ]);
            }
            DB::table('hospital_event_ad_pricing_groups')->where('id', $scope->id)
                ->update(['lock_version' => $scope->lock_version + 1, 'updated_at' => $now]);
        }, 3);

        return $this->show($group);
    }

    private function prices(string $group, array $prices): array
    {
        $expected = HospitalEventAd::placementsForGroup($group);
        $actual = array_keys($prices);
        sort($expected);
        sort($actual);
        if ($actual !== $expected) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '선택한 탭의 모든 광고 위치에 가격을 입력해 주세요.');
        }

        return array_map(fn ($price) => (int) $price, $prices);
    }

    private function rows(string $group, ?HospitalEventAdPricingVersion $version): array
    {
        return array_map(fn (string $placement) => [
            'placement' => $placement,
            'label' => HospitalEventAd::placementLabel($placement),
            'price' => (int) ($version?->prices[$placement] ?? HospitalEventAd::placementCost($placement)),
            'updated_at' => $version?->created_at?->toIso8601String(),
        ], HospitalEventAd::placementsForGroup($group));
    }
}
