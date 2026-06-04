<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Dto\Staff;

final readonly class HospitalSummaryForStaffDto
{
    public function __construct(
        public int $dailyVisitors,
        public int $monthlyVisitors,
        public int $todayRegisteredHospitals,
        public int $registeredWithin7Days,
        public int $registeredWithin30Days,
        public int $registeredWithin1Year,
        public int $dormantHospitals,
        public int $totalHospitals,
        public int $pendingReviewHospitals,
        public int $rejectedReviewHospitals,
        public int $suspendedHospitals,
        public int $withdrawnHospitals,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dailyVisitors: (int) ($data['daily_visitors'] ?? 0),
            monthlyVisitors: (int) ($data['monthly_visitors'] ?? 0),
            todayRegisteredHospitals: (int) ($data['today_registered_hospitals'] ?? 0),
            registeredWithin7Days: (int) ($data['registered_within_7_days'] ?? 0),
            registeredWithin30Days: (int) ($data['registered_within_30_days'] ?? 0),
            registeredWithin1Year: (int) ($data['registered_within_1_year'] ?? 0),
            dormantHospitals: (int) ($data['dormant_hospitals'] ?? 0),
            totalHospitals: (int) ($data['total_hospitals'] ?? 0),
            pendingReviewHospitals: (int) ($data['pending_review_hospitals'] ?? 0),
            rejectedReviewHospitals: (int) ($data['rejected_review_hospitals'] ?? 0),
            suspendedHospitals: (int) ($data['suspended_hospitals'] ?? 0),
            withdrawnHospitals: (int) ($data['withdrawn_hospitals'] ?? 0),
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'daily_visitors' => $this->dailyVisitors,
            'monthly_visitors' => $this->monthlyVisitors,
            'today_registered_hospitals' => $this->todayRegisteredHospitals,
            'registered_within_7_days' => $this->registeredWithin7Days,
            'registered_within_30_days' => $this->registeredWithin30Days,
            'registered_within_1_year' => $this->registeredWithin1Year,
            'dormant_hospitals' => $this->dormantHospitals,
            'total_hospitals' => $this->totalHospitals,
            'pending_review_hospitals' => $this->pendingReviewHospitals,
            'rejected_review_hospitals' => $this->rejectedReviewHospitals,
            'suspended_hospitals' => $this->suspendedHospitals,
            'withdrawn_hospitals' => $this->withdrawnHospitals,
        ];
    }
}
