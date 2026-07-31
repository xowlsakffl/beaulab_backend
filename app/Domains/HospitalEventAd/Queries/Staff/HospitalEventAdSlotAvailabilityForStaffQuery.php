<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class HospitalEventAdSlotAvailabilityForStaffQuery
{
    public function reservedCount(string $placement, ?int $categoryId, Carbon $startAt, ?int $excludeId = null): int
    {
        return $this->baseQuery($placement, $categoryId, $startAt, $excludeId)->count();
    }

    /**
     * @return Collection<int, HospitalEventAd>
     */
    public function reservedAds(
        string $placement,
        ?int $categoryId,
        Carbon $startAt,
        ?int $excludeId = null,
        ?array $allowStatuses = null,
    ): Collection {
        return $this->withSummaryRelations(
            $this->baseQuery($placement, $categoryId, $startAt, $excludeId, $allowStatuses),
        )
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<string>  $placements
     * @param  list<Carbon>  $startAts
     * @param  list<string>|null  $allowStatuses
     * @return Collection<int, HospitalEventAd>
     */
    public function reservedAdsForSlots(
        array $placements,
        ?int $categoryId,
        array $startAts,
        ?array $allowStatuses = null,
    ): Collection {
        if ($placements === [] || $startAts === []) {
            return new Collection;
        }

        $allowStatuses ??= [
            HospitalEventAd::ALLOW_PENDING,
            HospitalEventAd::ALLOW_REVIEWING,
            HospitalEventAd::ALLOW_APPROVED,
        ];
        $startAtValues = collect($startAts)
            ->map(static fn (Carbon $startAt): string => $startAt->toDateTimeString())
            ->unique()
            ->values()
            ->all();

        $builder = HospitalEventAd::query()
            ->whereIn('placement', $placements)
            ->whereIn('start_at', $startAtValues)
            ->whereIn('allow_status', $allowStatuses)
            ->when($categoryId !== null, fn (Builder $query) => $query
                ->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery
                    ->where('categories.id', $categoryId)));

        return $this->withSummaryRelations($builder)
            ->orderBy('placement')
            ->orderBy('start_at')
            ->orderBy('id')
            ->get();
    }

    private function baseQuery(
        string $placement,
        ?int $categoryId,
        Carbon $startAt,
        ?int $excludeId = null,
        ?array $allowStatuses = null,
    ): Builder {
        $allowStatuses ??= [
            HospitalEventAd::ALLOW_PENDING,
            HospitalEventAd::ALLOW_REVIEWING,
            HospitalEventAd::ALLOW_APPROVED,
        ];

        return HospitalEventAd::query()
            ->where('placement', $placement)
            ->where('start_at', $startAt)
            ->whereIn('allow_status', $allowStatuses)
            ->when($categoryId !== null, fn ($query) => $query
                ->whereHas('categories', fn ($categoryQuery) => $categoryQuery
                    ->where('categories.id', $categoryId)))
            ->when($excludeId !== null, fn ($query) => $query->whereKeyNot($excludeId));
    }

    private function withSummaryRelations(Builder $builder): Builder
    {
        return $builder->with([
            'hospital:id,name',
            'hospitalEvent:id,name',
            'categories:id,name,code,full_path',
            'managerStaff:id,name',
        ]);
    }
}
