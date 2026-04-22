<?php

namespace App\Domains\BeautyExpert\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use Illuminate\Support\Collection;

/**
 * BeautyExpertForStaffDetailDto DTO.
 */
final readonly class BeautyExpertForStaffDetailDto
{
    /**
     * @param array<int, mixed> $educations
     * @param array<int, mixed> $careers
     * @param array<int, mixed> $etcContents
     * @param array<int, array<string, mixed>> $educationCertificateImage
     * @param array<int, array<string, mixed>> $etcCertificateImage
     * @param array<int, array<string, mixed>> $categories
     */
    public function __construct(
        public int $id,
        public int $beautyId,
        public int $sortOrder,
        public string $name,
        public ?string $gender,
        public ?string $position,
        public ?string $careerStartedAt,
        public array $educations,
        public array $careers,
        public array $etcContents,
        public string $status,
        public string $allowStatus,
        public ?array $profileImage,
        public array $educationCertificateImage,
        public array $etcCertificateImage,
        public array $categories,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(BeautyExpert $expert): self
    {
        return new self(
            id: (int) $expert->id,
            beautyId: (int) $expert->beauty_id,
            sortOrder: (int) $expert->sort_order,
            name: (string) $expert->name,
            gender: $expert->gender,
            position: $expert->position,
            careerStartedAt: $expert->career_started_at?->toDateString(),
            educations: self::arrayValue($expert->educations),
            careers: self::arrayValue($expert->careers),
            etcContents: self::arrayValue($expert->etc_contents),
            status: (string) $expert->status,
            allowStatus: (string) $expert->allow_status,
            profileImage: self::formatMedia($expert->profileImage),
            educationCertificateImage: self::formatMediaList($expert->educationCertificateImages),
            etcCertificateImage: self::formatMediaList($expert->etcCertificateImages),
            categories: self::resolveCategories($expert)
                ->map(fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'name' => (string) $category->name,
                    'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                ])
                ->values()
                ->all(),
            createdAt: $expert->created_at?->toISOString(),
            updatedAt: $expert->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'beauty_id' => $this->beautyId,
            'sort_order' => $this->sortOrder,
            'name' => $this->name,
            'gender' => $this->gender,
            'position' => $this->position,
            'career_started_at' => $this->careerStartedAt,
            'educations' => $this->educations,
            'careers' => $this->careers,
            'etc_contents' => $this->etcContents,
            'status' => $this->status,
            'allow_status' => $this->allowStatus,
            'profile_image' => $this->profileImage,
            'education_certificate_image' => $this->educationCertificateImage,
            'etc_certificate_image' => $this->etcCertificateImage,
            'categories' => $this->categories,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    /**
     * @return array<int, mixed>
     */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
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
     * @param Collection<int, Media>|iterable<int, Media>|null $mediaList
     */
    private static function formatMediaList(Collection|iterable|null $mediaList): array
    {
        return collect($mediaList)->map(fn (Media $media): array => self::formatMedia($media))->values()->all();
    }

    /**
     * @return Collection<int, Category>
     */
    private static function resolveCategories(BeautyExpert $expert): Collection
    {
        if (! $expert->relationLoaded('categories')) {
            return collect();
        }

        return $expert->categories;
    }
}
