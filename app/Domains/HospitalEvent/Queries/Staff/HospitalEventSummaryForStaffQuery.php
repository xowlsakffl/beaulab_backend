<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Database\Eloquent\Builder;

final class HospitalEventSummaryForStaffQuery
{
    /**
     * @return array<string, int>
     */
    public function get(): array
    {
        return StaffSummaryCache::remember(
            StaffSummaryCache::DOMAIN_HOSPITAL_EVENT,
            fn (): array => $this->uncachedSummary(),
        );
    }

    /**
     * @return array<string, int>
     */
    private function uncachedSummary(): array
    {
        $now = now();
        $recentStart = $now->copy()->subDays(30)->startOfDay();
        $today = $now->copy()->startOfDay();
        $endingUntil = $now->copy()->addDays(30)->endOfDay();

        $baseQuery = HospitalEvent::query();

        return [
            'active_events' => (clone $baseQuery)
                ->where('hospital_status', HospitalEvent::HOSPITAL_STATUS_PUBLIC)
                ->where('admin_status', HospitalEvent::ADMIN_STATUS_NORMAL)
                ->where('allow_status', HospitalEvent::ALLOW_APPROVED)
                ->count(),
            'recent_created_events' => (clone $baseQuery)
                ->where('created_at', '>=', $recentStart)
                ->count(),
            'ending_soon_events' => (clone $baseQuery)
                ->where('is_event_period_unlimited', false)
                ->whereNotNull('event_end_at')
                ->whereBetween('event_end_at', [$today, $endingUntil])
                ->count(),
            'recent_stopped_events' => $this->recentPrivateOrEndedEventCount($recentStart, $now),
            'pending_events' => (clone $baseQuery)
                ->where('allow_status', HospitalEvent::ALLOW_PENDING)
                ->count(),
            'reviewing_events' => (clone $baseQuery)
                ->where('allow_status', HospitalEvent::ALLOW_REVIEWING)
                ->count(),
            'approved_events' => (clone $baseQuery)
                ->where('allow_status', HospitalEvent::ALLOW_APPROVED)
                ->count(),
            'rejected_events' => (clone $baseQuery)
                ->where('allow_status', HospitalEvent::ALLOW_REJECTED)
                ->count(),
        ];
    }

    private function recentPrivateOrEndedEventCount(mixed $recentStart, mixed $now): int
    {
        return HospitalEvent::query()
            ->where(fn (Builder $query) => $this->applyRecentPrivateOrEndedFilter($query, $recentStart, $now))
            ->count();
    }

    private function applyRecentPrivateOrEndedFilter(Builder $builder, mixed $recentStart, mixed $now): void
    {
        $privateEventIds = $this->recentHospitalPrivateEventIds($recentStart);

        $builder->where(function (Builder $query) use ($privateEventIds, $recentStart, $now): void {
            $query
                ->where(function (Builder $privateQuery) use ($privateEventIds): void {
                    if ($privateEventIds === []) {
                        $privateQuery->whereRaw('1 = 0');

                        return;
                    }

                    $privateQuery
                        ->where('hospital_status', HospitalEvent::HOSPITAL_STATUS_PRIVATE)
                        ->whereIn('id', $privateEventIds);
                })
                ->orWhere(fn (Builder $endedQuery) => $this->applyRecentlyEndedFilter($endedQuery, $recentStart, $now));
        });
    }

    private function applyRecentlyEndedFilter(Builder $builder, mixed $recentStart, mixed $now): void
    {
        $builder
            ->where('is_event_period_unlimited', false)
            ->whereNotNull('event_end_at')
            ->whereBetween('event_end_at', [
                $recentStart,
                $now->copy()->subDay()->endOfDay(),
            ]);
    }

    /**
     * @return array<int, int>
     */
    private function recentHospitalPrivateEventIds(mixed $recentStart): array
    {
        return OperationHistory::query()
            ->where('target_type', HospitalEvent::class)
            ->where('actor_kind', OperationHistory::ACTOR_KIND_HOSPITAL)
            ->whereHas('changes', static fn ($query) => $query
                ->where('field_key', 'hospital_status')
                ->whereIn('after_display', [HospitalEvent::HOSPITAL_STATUS_PRIVATE, '비공개']))
            ->where('created_at', '>=', $recentStart)
            ->distinct()
            ->pluck('target_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();
    }
}
