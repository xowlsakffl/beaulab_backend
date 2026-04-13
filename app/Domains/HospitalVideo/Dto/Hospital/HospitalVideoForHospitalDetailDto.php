<?php

namespace App\Domains\HospitalVideo\Dto\Hospital;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Collection;

/**
 * HospitalVideoForHospitalDetailDto 역할 정의.
 * 병원 동영상 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class HospitalVideoForHospitalDetailDto
{
    public function __construct(public array $video) {}

    public static function fromModel(HospitalVideo $video): self
    {
        return new self([
            'id' => (int) $video->id,
            'hospital_id' => (int) $video->hospital_id,
            'doctor_id' => $video->doctor_id ? (int) $video->doctor_id : null,
            'submitted_by_account_id' => $video->submitted_by_account_id ? (int) $video->submitted_by_account_id : null,
            'title' => (string) $video->title,
            'description' => $video->description,
            'is_usage_consented' => (bool) $video->is_usage_consented,
            'distribution_channel' => (string) $video->distribution_channel,
            'status' => (string) $video->status,
            'allow_status' => (string) $video->allow_status,
            'publish_start_at' => $video->publish_start_at?->toISOString(),
            'publish_end_at' => $video->publish_end_at?->toISOString(),
            'thumbnail_file' => self::formatMedia($video->thumbnailMedia),
            'video_file' => self::formatMedia($video->videoFileMedia),
            'categories' => self::resolveCategories($video)
                ->map(fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'name' => (string) $category->name,
                    'full_path' => (string) ($category->full_path ?? ''),
                    'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                ])
                ->values()
                ->all(),
            'created_at' => $video->created_at?->toISOString(),
            'updated_at' => $video->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->video;
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
