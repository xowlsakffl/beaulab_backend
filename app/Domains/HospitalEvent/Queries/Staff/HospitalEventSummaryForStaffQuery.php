<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalEvent\Models\HospitalEvent;

final class HospitalEventSummaryForStaffQuery
{
    /**
     * @return array<string, int>
     */
    public function get(): array
    {
        $now = now();
        $recentStart = $now->copy()->subDays(30)->startOfDay();
        $today = $now->copy()->startOfDay();
        $endingUntil = $now->copy()->addDays(30)->endOfDay();

        $baseQuery = HospitalEvent::query();

        return [
            'active_events' => (clone $baseQuery)
                ->where('status', HospitalEvent::STATUS_ACTIVE)
                ->count(),
            'recent_created_events' => (clone $baseQuery)
                ->where('created_at', '>=', $recentStart)
                ->count(),
            'ending_soon_events' => (clone $baseQuery)
                ->where('is_event_period_unlimited', false)
                ->whereNotNull('event_end_at')
                ->whereBetween('event_end_at', [$today, $endingUntil])
                ->count(),
            'recent_stopped_events' => $this->recentStoppedEventCount($recentStart),
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

    private function recentStoppedEventCount(mixed $recentStart): int
    {
        return OperationHistory::query()
            ->where('target_type', HospitalEvent::class)
            ->whereHas('changes', static fn ($query) => $query
                ->where('field_key', 'status')
                ->whereIn('after_display', [HospitalEvent::STATUS_INACTIVE, '미노출']))
            ->where('created_at', '>=', $recentStart)
            ->distinct('target_id')
            ->count('target_id');
    }
}
