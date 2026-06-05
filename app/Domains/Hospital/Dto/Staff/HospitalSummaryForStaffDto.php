<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Dto\Staff;

final readonly class HospitalSummaryForStaffDto
{
    public function __construct(
        public int $dormantHospitals,
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
            dormantHospitals: (int) ($data['dormant_hospitals'] ?? 0),
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
            'dormant_hospitals' => $this->dormantHospitals,
            'pending_review_hospitals' => $this->pendingReviewHospitals,
            'rejected_review_hospitals' => $this->rejectedReviewHospitals,
            'suspended_hospitals' => $this->suspendedHospitals,
            'withdrawn_hospitals' => $this->withdrawnHospitals,
        ];
    }
}
