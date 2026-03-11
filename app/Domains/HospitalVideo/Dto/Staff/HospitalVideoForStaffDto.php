<?php

namespace App\Domains\HospitalVideo\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Collection;

final readonly class HospitalVideoForStaffDto
{
    public function __construct(
        public int $id,
        public int $hospitalId,
        public ?int $doctorId,
        public string $title,
        public string $distributionChannel,
        public ?string $externalVideoId,
        public ?string $externalVideoUrl,
        public int $durationSeconds,
        public string $status,
        public string $allowStatus,
        public int $viewCount,
        public int $likeCount,
        public ?string $publishStartAt,
        public ?string $publishEndAt,
        public bool $isPublishPeriodUnlimited,
        public string $createdAt,
        public string $updatedAt,
        public ?array $categories,
    ) {}

    public static function fromModel(HospitalVideo $video): self
    {
        return new self(
            id: $video->id,
            hospitalId: (int) $video->hospital_id,
            doctorId: $video->doctor_id,
            title: $video->title,
            distributionChannel: $video->distribution_channel,
            externalVideoId: $video->external_video_id,
            externalVideoUrl: $video->external_video_url,
            durationSeconds: (int) $video->duration_seconds,
            status: $video->status,
            allowStatus: $video->allow_status,
            viewCount: (int) $video->view_count,
            likeCount: (int) $video->like_count,
            publishStartAt: $video->publish_start_at?->toISOString(),
            publishEndAt: $video->publish_end_at?->toISOString(),
            isPublishPeriodUnlimited: (bool) $video->is_publish_period_unlimited,
            categories: $video->relationLoaded('categories')
                ? self::resolveCategories($video)
                    ->map(fn (Category $category): array => [
                        'id' => (int) $category->id,
                        'name' => (string) $category->name,
                        'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                    ])
                    ->values()
                    ->all()
                : null,
            createdAt: $video->created_at?->toISOString() ?? '',
            updatedAt: $video->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'hospital_id' => $this->hospitalId,
            'doctor_id' => $this->doctorId,
            'title' => $this->title,
            'distribution_channel' => $this->distributionChannel,
            'external_video_id' => $this->externalVideoId,
            'external_video_url' => $this->externalVideoUrl,
            'duration_seconds' => $this->durationSeconds,
            'status' => $this->status,
            'allow_status' => $this->allowStatus,
            'view_count' => $this->viewCount,
            'like_count' => $this->likeCount,
            'publish_start_at' => $this->publishStartAt,
            'publish_end_at' => $this->publishEndAt,
            'is_publish_period_unlimited' => $this->isPublishPeriodUnlimited,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->categories !== null) {
            $data['categories'] = $this->categories;
        }

        return $data;
    }

    /**
     * @return Collection<int, Category>
     */
    private static function resolveCategories(HospitalVideo $video): Collection
    {
        if (! $video->relationLoaded('categories')) {
            return collect();
        }

        return $video->categories;
    }
}
