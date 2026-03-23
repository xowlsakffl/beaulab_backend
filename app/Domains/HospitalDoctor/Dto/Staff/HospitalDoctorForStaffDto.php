<?php

namespace App\Domains\HospitalDoctor\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;

final readonly class HospitalDoctorForStaffDto
{
    public function __construct(
        public int $id,
        public int $hospitalId,
        public ?string $hospitalName,
        public string $name,
        public ?string $gender,
        public ?string $position,
        public bool $isSpecialist,
        public array $careers,
        public int $sortOrder,
        public string $allowStatus,
        public string $status,
        public int $viewCount,
        public string $createdAt,
        public string $updatedAt,
        public ?array $profileImage,
        public ?array $categories = null,
    ) {}

    public static function fromModel(HospitalDoctor $doctor): self
    {
        return new self(
            id: (int) $doctor->id,
            hospitalId: (int) $doctor->hospital_id,
            hospitalName: $doctor->relationLoaded('hospital') ? $doctor->hospital?->name : null,
            name: (string) $doctor->name,
            gender: $doctor->gender,
            position: $doctor->position,
            isSpecialist: (bool) $doctor->is_specialist,
            careers: is_array($doctor->careers) ? $doctor->careers : [],
            sortOrder: (int) $doctor->sort_order,
            allowStatus: (string) $doctor->allow_status,
            status: (string) $doctor->status,
            viewCount: (int) $doctor->view_count,
            createdAt: $doctor->created_at?->toISOString() ?? '',
            updatedAt: $doctor->updated_at?->toISOString() ?? '',
            profileImage: self::formatMedia($doctor->relationLoaded('profileImage') ? $doctor->profileImage : null),
            categories: $doctor->relationLoaded('categories')
                ? $doctor->categories
                    ->map(fn (Category $category): array => [
                        'name' => (string) $category->name,
                    ])
                    ->values()
                    ->all()
                : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'hospital_id' => $this->hospitalId,
            'hospital_name' => $this->hospitalName,
            'name' => $this->name,
            'gender' => $this->gender,
            'position' => $this->position,
            'is_specialist' => $this->isSpecialist,
            'careers' => $this->careers,
            'sort_order' => $this->sortOrder,
            'allow_status' => $this->allowStatus,
            'status' => $this->status,
            'view_count' => $this->viewCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'profile_image' => $this->profileImage,
        ];

        if ($this->categories !== null) {
            $data['categories'] = $this->categories;
        }

        return $data;
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
}
