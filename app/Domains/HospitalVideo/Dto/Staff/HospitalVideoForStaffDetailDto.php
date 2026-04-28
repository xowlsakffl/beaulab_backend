<?php

namespace App\Domains\HospitalVideo\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Collection;

/**
 * HospitalVideoForStaffDetailDto DTO.
 */
final readonly class HospitalVideoForStaffDetailDto
{
    public function __construct(
        public int $id,
        public int $hospitalId,
        public ?string $hospitalName,
        public ?string $hospitalBusinessNumber,
        public ?int $doctorId,
        public ?string $doctorName,
        public string $title,
        public ?string $description,
        public string $distributionChannel,
        public ?string $externalVideoId,
        public ?string $externalVideoUrl,
        public ?int $thumbnailMediaId,
        public ?array $thumbnailFile,
        public ?int $videoFileMediaId,
        public ?array $videoFile,
        public int $durationSeconds,
        public string $status,
        public string $allowStatus,
        public int $viewCount,
        public int $likeCount,
        public ?string $allowedAt,
        public ?string $publishStartAt,
        public ?string $publishEndAt,
        public bool $isPublishPeriodUnlimited,
        public array $categories,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(HospitalVideo $video): self
    {
        return new self(
            id: (int) $video->id,
            hospitalId: (int) $video->hospital_id,
            hospitalName: $video->hospital?->name,
            hospitalBusinessNumber: $video->hospital?->businessRegistration?->business_number,
            doctorId: $video->doctor_id ? (int) $video->doctor_id : null,
            doctorName: $video->doctor?->name,
            title: (string) $video->title,
            description: $video->description,
            distributionChannel: (string) $video->distribution_channel,
            externalVideoId: $video->external_video_id,
            externalVideoUrl: $video->external_video_url,
            thumbnailMediaId: $video->thumbnailMedia?->id ? (int) $video->thumbnailMedia->id : null,
            thumbnailFile: self::formatMedia($video->thumbnailMedia),
            videoFileMediaId: $video->videoFileMedia?->id ? (int) $video->videoFileMedia->id : null,
            videoFile: self::formatMedia($video->videoFileMedia),
            durationSeconds: (int) $video->duration_seconds,
            status: (string) $video->status,
            allowStatus: (string) $video->allow_status,
            viewCount: (int) $video->view_count,
            likeCount: (int) $video->like_count,
            allowedAt: $video->allowed_at?->toISOString(),
            publishStartAt: $video->publish_start_at?->toISOString(),
            publishEndAt: $video->publish_end_at?->toISOString(),
            isPublishPeriodUnlimited: (bool) $video->is_publish_period_unlimited,
            categories: self::categories($video),
            createdAt: $video->created_at?->toISOString(),
            updatedAt: $video->updated_at?->toISOString(),
            deletedAt: $video->deleted_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'hospital_id' => $this->hospitalId,
            'hospital_name' => $this->hospitalName,
            'hospital_business_number' => $this->hospitalBusinessNumber,
            'doctor_id' => $this->doctorId,
            'doctor_name' => $this->doctorName,
            'title' => $this->title,
            'description' => $this->description,
            'distribution_channel' => $this->distributionChannel,
            'external_video_id' => $this->externalVideoId,
            'external_video_url' => $this->externalVideoUrl,
            'thumbnail_media_id' => $this->thumbnailMediaId,
            'thumbnail_file' => $this->thumbnailFile,
            'video_file_media_id' => $this->videoFileMediaId,
            'video_file' => $this->videoFile,
            'duration_seconds' => $this->durationSeconds,
            'status' => $this->status,
            'allow_status' => $this->allowStatus,
            'view_count' => $this->viewCount,
            'like_count' => $this->likeCount,
            'allowed_at' => $this->allowedAt,
            'publish_start_at' => $this->publishStartAt,
            'publish_end_at' => $this->publishEndAt,
            'is_publish_period_unlimited' => $this->isPublishPeriodUnlimited,
            'categories' => $this->categories,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];

        return $data;
    }

    private static function categories(HospitalVideo $video): array
    {
        return self::resolveCategories($video)
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'domain' => (string) ($category->domain ?? ''),
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
            'id' => $media->id,
            'collection' => $media->collection,
            'disk' => $media->disk,
            'path' => $media->path,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'sort_order' => $media->sort_order,
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
