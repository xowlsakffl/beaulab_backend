<?php

namespace App\Domains\HospitalVideo\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalVideo\Models\HospitalVideo;

/**
 * HospitalVideoForStaffDto 역할 정의.
 * 병원 동영상 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class HospitalVideoForStaffDto
{
    public function __construct(
        public int $id,
        public ?array $hospital,
        public ?array $doctor,
        public string $title,
        public ?array $thumbnailFile,
        public string $distributionChannel,
        public ?string $externalVideoId,
        public ?string $externalVideoUrl,
        public int $durationSeconds,
        public string $status,
        public string $allowStatus,
        public int $viewCount,
        public int $likeCount,
        public ?string $allowedAt,
        public ?string $publishStartAt,
        public ?string $publishEndAt,
        public bool $isPublishPeriodUnlimited,
        public string $createdAt,
        public string $updatedAt,
        public ?array $categories = null,
    ) {}

    public static function fromModel(HospitalVideo $video): self
    {
        return new self(
            id: $video->id,
            hospital: self::hospital($video),
            doctor: self::doctor($video),
            title: $video->title,
            thumbnailFile: self::thumbnailFile($video),
            distributionChannel: $video->distribution_channel,
            externalVideoId: $video->external_video_id,
            externalVideoUrl: $video->external_video_url,
            durationSeconds: (int) $video->duration_seconds,
            status: $video->status,
            allowStatus: $video->allow_status,
            viewCount: (int) $video->view_count,
            likeCount: (int) $video->like_count,
            allowedAt: $video->allowed_at?->toISOString(),
            publishStartAt: $video->publish_start_at?->toISOString(),
            publishEndAt: $video->publish_end_at?->toISOString(),
            isPublishPeriodUnlimited: (bool) $video->is_publish_period_unlimited,
            categories: self::categories($video),
            createdAt: $video->created_at?->toISOString() ?? '',
            updatedAt: $video->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'hospital' => $this->hospital,
            'doctor' => $this->doctor,
            'title' => $this->title,
            'thumbnail_file' => $this->thumbnailFile,
            'distribution_channel' => $this->distributionChannel,
            'external_video_id' => $this->externalVideoId,
            'external_video_url' => $this->externalVideoUrl,
            'duration_seconds' => $this->durationSeconds,
            'status' => $this->status,
            'allow_status' => $this->allowStatus,
            'view_count' => $this->viewCount,
            'like_count' => $this->likeCount,
            'allowed_at' => $this->allowedAt,
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

    private static function hospital(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('hospital') || ! $video->hospital) {
            return null;
        }

        $businessNumber = null;
        if ($video->hospital->relationLoaded('businessRegistration') && $video->hospital->businessRegistration) {
            $businessNumber = $video->hospital->businessRegistration->business_number;
        }

        return [
            'id' => (int) $video->hospital->id,
            'name' => (string) $video->hospital->name,
            'business_number' => $businessNumber,
        ];
    }

    private static function doctor(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('doctor') || ! $video->doctor) {
            return null;
        }

        $attributes = $video->doctor->getAttributes();

        return [
            'id' => (int) $video->doctor->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'position' => $attributes['position'] ?? null,
        ];
    }

    private static function categories(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('categories')) {
            return null;
        }

        return $video->categories
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'code' => (string) ($category->code ?? ''),
                'domain' => (string) ($category->domain ?? ''),
                'name' => (string) $category->name,
                'full_path' => (string) ($category->full_path ?? ''),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->values()
            ->all();
    }

    private static function thumbnailFile(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('thumbnailMedia')) {
            return null;
        }

        return self::media($video->thumbnailMedia);
    }

    private static function media(?Media $media): ?array
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
}
