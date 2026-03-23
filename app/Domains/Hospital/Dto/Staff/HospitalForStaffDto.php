<?php

namespace App\Domains\Hospital\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalFeature\Models\HospitalFeature;

final readonly class HospitalForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $address,
        public ?string $addressDetail,
        public ?string $tel,
        public int $viewCount,
        public string $allowStatus,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
        public ?array $logo,
        public ?array $categories = null,
        public ?array $features = null,
    ) {}

    public static function fromModel(Hospital $hospital): self
    {
        return new self(
            id: $hospital->id,
            name: $hospital->name,
            address: $hospital->address,
            addressDetail: $hospital->address_detail,
            tel: $hospital->tel,
            viewCount: (int) $hospital->view_count,
            allowStatus: $hospital->allow_status,
            status: $hospital->status,
            createdAt: $hospital->created_at?->toISOString() ?? '',
            updatedAt: $hospital->updated_at?->toISOString() ?? '',
            logo: self::formatMedia($hospital->relationLoaded('logoMedia') ? $hospital->logoMedia : null),
            categories: $hospital->relationLoaded('categories')
                ? $hospital->categories
                    ->map(fn (Category $category): array => [
                        'name' => (string) $category->name,
                    ])
                    ->values()
                    ->all()
                : null,
            features: $hospital->relationLoaded('features')
                ? $hospital->features
                    ->map(fn (HospitalFeature $feature): array => [
                        'code' => (string) $feature->code,
                        'name' => (string) $feature->name,
                    ])
                    ->values()
                    ->all()
                : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id'           => $this->id,
            'name'         => $this->name,
            'address'      => $this->address,
            'address_detail' => $this->addressDetail,
            'tel'          => $this->tel,
            'view_count'   => $this->viewCount,
            'allow_status' => $this->allowStatus,
            'status'       => $this->status,
            'created_at'   => $this->createdAt,
            'updated_at'   => $this->updatedAt,
            'logo'         => $this->logo,
        ];

        if ($this->categories !== null) {
            $data['categories'] = $this->categories;
        }

        if ($this->features !== null) {
            $data['features'] = $this->features;
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
