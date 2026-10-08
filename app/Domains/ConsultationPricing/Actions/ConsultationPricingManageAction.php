<?php

namespace App\Domains\ConsultationPricing\Actions;

use App\Common\Authorization\AccessPermissions;
use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\ConsultationPricing\Models\ConsultationPricingVersion;
use App\Domains\ConsultationPricing\Queries\ConsultationPricingQuery;
use App\Domains\ConsultationPricing\Support\ConsultationPricingResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ConsultationPricingManageAction
{
    public function __construct(private readonly ConsultationPricingQuery $query, private readonly ConsultationPricingResolver $resolver) {}

    private function key(array $input): string
    {
        if ($input['type'] !== 'EVENT') {
            if (! empty($input['category_id'])) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '고정 단가에는 카테고리를 지정할 수 없습니다.');
            }

            return $input['type'];
        }
        $division = $input['division'] ?? 'SURGERY';
        $id = (int) ($input['category_id'] ?? 0);
        if ($id && ! $this->resolver->categories($division)->contains('id', $id)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '사용 가능한 대분류를 선택해 주세요.');
        }

        return "EVENT:{$division}:{$id}";
    }

    public function show(array $input): array
    {
        Gate::authorize(AccessPermissions::BEAULAB_DB_PRICING_MANAGE);
        $key = $this->key($input);
        $now = CarbonImmutable::now();
        $scope = DB::table('consultation_pricing_scopes')->where('scope_key', $key)->first();
        $pending = $this->query->pending($key, $now);

        return [
            'key' => $key, 'lock_version' => (int) ($scope?->lock_version ?? 0),
            'server_time' => $now->toIso8601String(),
            'current' => $this->resolver->table($key, $now),
            'default_table' => $input['type'] === 'EVENT' ? $this->resolver->table('EVENT:'.($input['division'] ?? 'SURGERY').':0', $now) : null,
            'custom_ranges' => $this->query->versions($key)->where('mode', 'CUSTOM')->where('effective_at', '<=', $now)->orderByDesc('effective_at')->orderByDesc('id')->first()?->ranges,
            'pending' => $pending?->only(['id', 'mode', 'ranges', 'fixed_price', 'effective_at']),
            'pending_table' => $pending ? $this->resolver->table($key, $pending->effective_at) : null,
            'categories' => $input['type'] === 'EVENT' ? $this->resolver->categories($input['division'] ?? 'SURGERY')->map(fn ($c) => ['id' => $c->id, 'name' => $c->displayNameFor('HOSPITAL_EVENT')])->all() : [],
        ];
    }

    public function save(array $input, bool $cancel = false): array
    {
        Gate::authorize(AccessPermissions::BEAULAB_DB_PRICING_MANAGE);
        $key = $this->key($input);
        DB::transaction(function () use ($key, $input, $cancel) {
            DB::table('consultation_pricing_scopes')->insertOrIgnore(['scope_key' => $key, 'created_at' => now(), 'updated_at' => now()]);
            $scope = DB::table('consultation_pricing_scopes')->where('scope_key', $key)->lockForUpdate()->first();
            if ((int) $scope->lock_version !== (int) $input['lock_version']) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '다른 관리자가 단가를 변경했습니다. 다시 조회한 뒤 저장해 주세요.');
            }
            $now = CarbonImmutable::now();
            $pending = $this->query->pending($key, $now);
            if ($cancel) {
                if (! $pending || (int) $pending->id !== (int) $input['pending_id']) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '예약이 이미 적용되었거나 변경되었습니다. 다시 조회해 주세요.');
                }
                $pending->update(['cancelled_at' => $now, 'cancelled_by' => auth()->id()]);
            } else {
                if ($pending) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '기존 적용 예약을 취소한 뒤 저장해 주세요.');
                }
                $mode = $input['mode'];
                $event = $input['type'] === 'EVENT';
                if ($mode === 'DEFAULT' && (! $event || empty($input['category_id']))) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '카테고리에서만 기본값 사용을 선택할 수 있습니다.');
                }
                $ranges = $mode === 'CUSTOM' && $event ? $this->validateRanges($input['ranges'] ?? []) : null;
                $fixed = ! $event ? ($input['fixed_price'] ?? null) : null;
                if (! $event && $fixed === null) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '상담신청 단가를 입력해 주세요.');
                }
                $at = ! empty($input['effective_at']) ? CarbonImmutable::parse($input['effective_at'])->setTimezone(config('app.timezone')) : $now;
                if (! empty($input['effective_at']) && $at->lessThanOrEqualTo($now)) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '예약 시각은 현재 시각 이후로 선택해 주세요.');
                }
                ConsultationPricingVersion::create(['scope_id' => $scope->id, 'mode' => $mode, 'ranges' => $ranges, 'fixed_price' => $fixed, 'effective_at' => $at, 'created_by' => auth()->id()]);
            }
            DB::table('consultation_pricing_scopes')->where('id', $scope->id)->update(['lock_version' => $scope->lock_version + 1, 'updated_at' => $now]);
        }, 3);

        return $this->show($input);
    }

    private function validateRanges(array $ranges): array
    {
        if (! $ranges || count($ranges) > 100) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '가격 구간은 1개 이상 100개 이하로 설정해 주세요.');
        }
        $previous = 0;
        foreach ($ranges as $index => $range) {
            $upper = $range['upper'] ?? null;
            $last = $index === count($ranges) - 1;
            if (($last && $upper !== null) || (! $last && ($upper === null || $upper <= $previous))) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '구간 상한은 순서대로 커져야 하며 마지막 구간은 상한이 없어야 합니다.');
            }
            $previous = $upper;
        }

        return array_map(fn ($range) => ['upper' => isset($range['upper']) ? (int) $range['upper'] : null, 'price' => (int) $range['price']], $ranges);
    }
}
