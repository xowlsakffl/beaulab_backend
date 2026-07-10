<?php

declare(strict_types=1);

namespace App\Domains\HospitalVideo\Dto\Staff;

final readonly class HospitalVideoSummaryForStaffDto
{
    public function __construct(
        private int $normalVideos,
        private int $limitedVideos,
        private int $reportedVideos,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            normalVideos: (int) ($data['normal_videos'] ?? 0),
            limitedVideos: (int) ($data['limited_videos'] ?? 0),
            reportedVideos: (int) ($data['reported_videos'] ?? 0),
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'normal_videos' => $this->normalVideos,
            'limited_videos' => $this->limitedVideos,
            'reported_videos' => $this->reportedVideos,
        ];
    }
}
