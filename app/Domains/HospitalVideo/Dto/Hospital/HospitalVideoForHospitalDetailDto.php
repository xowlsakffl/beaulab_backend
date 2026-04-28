<?php

namespace App\Domains\HospitalVideo\Dto\Hospital;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Collection;

/**
 * HospitalVideoForHospitalDetailDto DTO.
 */
final readonly class HospitalVideoForHospitalDetailDto
{
    public function __construct(
        public int $id,
        public int $hospitalId,
        public ?int $doctorId,
        public ?int $submittedByAccountId,
        public string $title,
        public ?string $description,
        public bool $isUsageConsented,
        public string $distributionChannel,
        public string $status,
        public string $allowStatus,
        public ?string $publishStartAt,
        public ?string $publishEndAt,
        public ?array $thumbnailFile,
        public ?array $videoFile,
        public array $categories,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(HospitalVideo $video): self
    {
        return new self(
            id: (int) $video->id,
            hospitalId: (int) $video->hospital_id,
            doctorId: $video->doctor_id ? (int) $video->doctor_id : null,
            submittedByAccountId: $video->submitted_by_account_id ? (int) $video->submitted_by_account_id : null,
            title: (string) $video->title,
            description: $video->description,
            isUsageConsented: (bool) $video->is_usage_consented,
            distributionChannel: (string) $video->distribution_channel,
            status: (string) $video->status,
            allowStatus: (string) $video->allow_status,
            publishStartAt: $video->publish_start_at?->toISOString(),
            publishEndAt: $video->publish_end_at?->toISOString(),
            thumbnailFile: self::formatMedia($video->thumbnailMedia),
            videoFile: self::formatMedia($video->videoFileMedia),
            categories: self::categories($video),
            createdAt: $video->created_at?->toISOString(),
            updatedAt: $video->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'hospital_id' => $this->hospitalId,
            'doctor_id' => $this->doctorId,
            'submitted_by_account_id' => $this->submittedByAccountId,
            'title' => $this->title,
            'description' => $this->description,
            'is_usage_consented' => $this->isUsageConsented,
            'distribution_channel' => $this->distributionChannel,
            'status' => $this->status,
            'allow_status' => $this->allowStatus,
            'publish_start_at' => $this->publishStartAt,
            'publish_end_at' => $this->publishEndAt,
            'thumbnail_file' => $this->thumbnailFile,
            'video_file' => $this->videoFile,
            'categories' => $this->categories,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    private static function categories(HospitalVideo $video): array
    {
        return self::resolveCategories($video)
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'full_path' => (string) ($category->full_path ?? ''),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->values()
            ->all();
    }

    private static function formatMedia(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'collection' => (string) $media->collection,
            'disk' => (string) $media->disk,
            'path' => (string) $media->path,
            'mime_type' => (string) $media->mime_type,
            'size' => (int) $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'sort_order' => (int) $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
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
