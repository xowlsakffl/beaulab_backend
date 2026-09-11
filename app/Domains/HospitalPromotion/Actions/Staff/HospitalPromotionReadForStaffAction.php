<?php

namespace App\Domains\HospitalPromotion\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalPromotion\Dto\HospitalPromotionAvailabilityDto;
use App\Domains\HospitalPromotion\Dto\HospitalPromotionDto;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use App\Domains\HospitalPromotion\Queries\HospitalPromotionQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalPromotionReadForStaffAction
{
    public function __construct(private readonly HospitalPromotionQuery $query) {}

    public function board(array $filters): array
    {
        Gate::authorize('viewAny', HospitalPromotion::class);
        $today = HospitalPromotion::today();

        return DB::transaction(function () use ($filters, $today): array {
            // Only slot occupancy is read without filters; hidden DTOs are never returned.
            $occupied = HospitalPromotion::query()->current($today)->get(['side', 'slot']);
            $current = $this->query->filter($this->query->listing()->current($today), $filters)->get();
            $result = ['today' => $today];
            foreach (HospitalPromotion::SIDES as $side) {
                $key = strtolower($side);
                $slots = [];
                foreach ([1, 2, 3] as $slot) {
                    $promotion = $current->first(fn ($item) => $item->side === $side && $item->slot === $slot);
                    $booked = $occupied->contains(fn ($item) => $item->side === $side && $item->slot === $slot);
                    $slots[] = ['slot' => $slot, 'promotion' => $promotion ? HospitalPromotionDto::fromModel($promotion, $today)->toArray() : null, 'filtered_out' => $booked && $promotion === null];
                }
                $upcoming = $this->query->filter($this->query->listing(), $filters)
                    ->where('side', $side)->where('start_date', '>', $today)
                    ->orderBy('start_date')->orderBy('slot')->orderBy('id')
                    ->paginate(perPage: 15, pageName: $key.'_page', page: (int) ($filters[$key.'_page'] ?? 1));
                $result[$key] = ['current' => $slots, 'upcoming' => PaginatedResponse::fromPaginator(
                    $upcoming, fn ($item) => HospitalPromotionDto::fromModel($item, $today)->toArray(),
                )];
            }

            return $result;
        });
    }

    public function ended(array $filters): array
    {
        Gate::authorize('viewAny', HospitalPromotion::class);
        $today = HospitalPromotion::today();
        $paginator = $this->query->filter($this->query->listing(), $filters)->where('end_date', '<', $today)
            ->orderByDesc('end_date')->orderByDesc('id')
            ->paginate(perPage: 15, page: (int) ($filters['page'] ?? 1));

        return PaginatedResponse::fromPaginator($paginator, fn ($item) => HospitalPromotionDto::fromModel($item, $today)->toArray());
    }

    public function detail(HospitalPromotion $promotion): array
    {
        Gate::authorize('view', $promotion);

        return HospitalPromotionDto::fromModel($promotion->load(['banner', 'creator:id,name']), HospitalPromotion::today(), true)->toArray();
    }

    public function histories(HospitalPromotion $promotion, array $filters): array
    {
        Gate::authorize('view', $promotion);
        $paginator = $promotion->operationHistories()->with(['actor', 'changes'])->paginate(
            perPage: (int) ($filters['operation_histories_per_page'] ?? 10),
            pageName: 'operation_histories_page', page: (int) ($filters['operation_histories_page'] ?? 1),
        );

        return PaginatedResponse::fromPaginator($paginator, fn ($item) => OperationHistoryDto::fromModel($item)->toArray());
    }

    public function availability(array $filters): array
    {
        Gate::authorize('checkAvailability', HospitalPromotion::class);
        $excludeId = isset($filters['exclude_id']) ? (int) $filters['exclude_id'] : null;
        if ($excludeId !== null) {
            $promotion = HospitalPromotion::query()->findOrFail($excludeId);
            Gate::authorize('update', $promotion);
        }

        return HospitalPromotionAvailabilityDto::fromModels(
            $this->query->scheduleConflicts($filters, $excludeId)->get(),
            Gate::allows('viewAny', HospitalPromotion::class),
        )->toArray();
    }
}
