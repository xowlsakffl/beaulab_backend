<?php

namespace App\Domains\HospitalVideo\Dto\Hospital;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalVideo\Models\HospitalVideo;

/**
 * HospitalVideoForHospitalDetailDto DTO.
 */
final readonly class HospitalVideoForHospitalDetailDto
{
    public function __construct(
        public int $id,
        public ?array $hospital,
        public ?array $doctor,
        public ?array $submittedByAccount,
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
            hospital: self::hospital($video),
            doctor: self::doctor($video),
            submittedByAccount: self::submittedByAccount($video),
            title: (string) $video->title,
            description: $video->description,
            isUsageConsented: (bool) $video->is_usage_consented,
            distributionChannel: (string) $video->distribution_channel,
            status: (string) $video->status,
            allowStatus: (string) $video->allow_status,
            publishStartAt: $video->publish_start_at?->toISOString(),
            publishEndAt: $video->publish_end_at?->toISOString(),
            thumbnailFile: self::thumbnailFile($video),
            videoFile: self::videoFile($video),
            categories: self::categories($video),
            createdAt: $video->created_at?->toISOString(),
            updatedAt: $video->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'hospital' => $this->hospital,
            'doctor' => $this->doctor,
            'submitted_by_account' => $this->submittedByAccount,
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

    private static function submittedByAccount(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('submittedByAccount') || ! $video->submittedByAccount) {
            return null;
        }

        $attributes = $video->submittedByAccount->getAttributes();

        return [
            'id' => (int) $video->submittedByAccount->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function categories(HospitalVideo $video): array
    {
        if (! $video->relationLoaded('categories')) {
            return [];
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

    private static function videoFile(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('videoFileMedia')) {
            return null;
        }

        return self::media($video->videoFileMedia);
    }

    private static function media(?Media $media): ?array
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
}
