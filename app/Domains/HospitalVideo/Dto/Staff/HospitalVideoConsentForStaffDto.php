<?php

declare(strict_types=1);

namespace App\Domains\HospitalVideo\Dto\Staff;

use App\Domains\Hospital\Models\Hospital;

final readonly class HospitalVideoConsentForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $department,
        public string $departmentLabel,
        public int $totalVideoCount,
        public int $visibleVideoCount,
        public ?string $videoConsentedAt,
        public string $status,
        public string $statusLabel,
    ) {}

    public static function fromModel(Hospital $hospital): self
    {
        return new self(
            id: (int) $hospital->id,
            name: (string) $hospital->name,
            department: (string) $hospital->department,
            departmentLabel: $hospital->departmentLabel(),
            totalVideoCount: (int) $hospital->total_video_count,
            visibleVideoCount: (int) $hospital->visible_video_count,
            videoConsentedAt: $hospital->video_consented_at?->toISOString(),
            status: (string) $hospital->status,
            statusLabel: Hospital::statusLabel($hospital->status),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'department' => $this->department,
            'department_label' => $this->departmentLabel,
            'total_video_count' => $this->totalVideoCount,
            'visible_video_count' => $this->visibleVideoCount,
            'video_consented_at' => $this->videoConsentedAt,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
        ];
    }
}
