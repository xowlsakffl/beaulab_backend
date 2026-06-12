<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Dto\Staff;

final readonly class HospitalEventSummaryForStaffDto
{
    public function __construct(
        private int $activeEvents,
        private int $recentCreatedEvents,
        private int $endingSoonEvents,
        private int $recentStoppedEvents,
        private int $pendingEvents,
        private int $reviewingEvents,
        private int $rejectedEvents,
        private int $partnerCanceledEvents,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            activeEvents: (int) ($data['active_events'] ?? 0),
            recentCreatedEvents: (int) ($data['recent_created_events'] ?? 0),
            endingSoonEvents: (int) ($data['ending_soon_events'] ?? 0),
            recentStoppedEvents: (int) ($data['recent_stopped_events'] ?? 0),
            pendingEvents: (int) ($data['pending_events'] ?? 0),
            reviewingEvents: (int) ($data['reviewing_events'] ?? 0),
            rejectedEvents: (int) ($data['rejected_events'] ?? 0),
            partnerCanceledEvents: (int) ($data['partner_canceled_events'] ?? 0),
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'active_events' => $this->activeEvents,
            'recent_created_events' => $this->recentCreatedEvents,
            'ending_soon_events' => $this->endingSoonEvents,
            'recent_stopped_events' => $this->recentStoppedEvents,
            'pending_events' => $this->pendingEvents,
            'reviewing_events' => $this->reviewingEvents,
            'rejected_events' => $this->rejectedEvents,
            'partner_canceled_events' => $this->partnerCanceledEvents,
        ];
    }
}
