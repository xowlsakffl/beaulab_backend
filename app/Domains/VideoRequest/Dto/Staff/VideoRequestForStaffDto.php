<?php

namespace App\Domains\VideoRequest\Dto\Staff;

use App\Domains\VideoRequest\Models\VideoRequest;

final readonly class VideoRequestForStaffDto
{
    public function __construct(
        public int $id,
        public ?int $hospitalId,
        public ?int $beautyId,
        public ?int $doctorId,
        public ?int $expertId,
        public string $title,
        public string $reviewStatus,
        public bool $isUsageConsented,
        public int $durationSeconds,
        public ?string $requestedPublishStartAt,
        public ?string $requestedPublishEndAt,
        public bool $isPublishPeriodUnlimited,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(VideoRequest $videoRequest): self
    {
        return new self(
            id: $videoRequest->id,
            hospitalId: $videoRequest->hospital_id,
            beautyId: $videoRequest->beauty_id,
            doctorId: $videoRequest->doctor_id,
            expertId: $videoRequest->expert_id,
            title: $videoRequest->title,
            reviewStatus: $videoRequest->review_status,
            isUsageConsented: (bool) $videoRequest->is_usage_consented,
            durationSeconds: (int) $videoRequest->duration_seconds,
            requestedPublishStartAt: $videoRequest->requested_publish_start_at?->toISOString(),
            requestedPublishEndAt: $videoRequest->requested_publish_end_at?->toISOString(),
            isPublishPeriodUnlimited: (bool) $videoRequest->is_publish_period_unlimited,
            createdAt: $videoRequest->created_at?->toISOString() ?? '',
            updatedAt: $videoRequest->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hospital_id' => $this->hospitalId,
            'beauty_id' => $this->beautyId,
            'doctor_id' => $this->doctorId,
            'expert_id' => $this->expertId,
            'title' => $this->title,
            'review_status' => $this->reviewStatus,
            'is_usage_consented' => $this->isUsageConsented,
            'duration_seconds' => $this->durationSeconds,
            'requested_publish_start_at' => $this->requestedPublishStartAt,
            'requested_publish_end_at' => $this->requestedPublishEndAt,
            'is_publish_period_unlimited' => $this->isPublishPeriodUnlimited,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
