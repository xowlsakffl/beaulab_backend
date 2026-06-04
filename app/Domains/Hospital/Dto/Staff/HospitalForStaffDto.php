<?php

namespace App\Domains\Hospital\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalFeature\Models\HospitalFeature;

/**
 * HospitalForStaffDto 역할 정의.
 * 병원 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class HospitalForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $department,
        public string $departmentLabel,
        public ?string $email,
        public ?string $tel,
        public int $viewCount,
        public array $evaluation,
        public array $reviewCounts,
        public string $allowStatus,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
        public ?array $account,
        public ?array $logo,
        public ?array $categories = null,
        public ?array $features = null,
    ) {}

    public static function fromModel(Hospital $hospital): self
    {
        return new self(
            id: $hospital->id,
            name: $hospital->name,
            department: (string) $hospital->department,
            departmentLabel: $hospital->departmentLabel(),
            email: $hospital->email,
            tel: $hospital->tel,
            viewCount: (int) $hospital->view_count,
            evaluation: [
                'count' => (int) $hospital->evaluation_count,
                'average_rating' => round((float) $hospital->evaluation_average_rating, 1),
            ],
            reviewCounts: [
                'surgery' => (int) $hospital->getAttribute('surgery_review_count'),
                'treatment' => (int) $hospital->getAttribute('treatment_review_count'),
            ],
            allowStatus: $hospital->allow_status,
            status: $hospital->status,
            createdAt: $hospital->created_at?->toISOString() ?? '',
            updatedAt: $hospital->updated_at?->toISOString() ?? '',
            account: self::account($hospital),
            logo: self::logo($hospital),
            categories: self::categories($hospital),
            features: self::features($hospital),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id'           => $this->id,
            'name'         => $this->name,
            'department'   => $this->department,
            'department_label' => $this->departmentLabel,
            'email'        => $this->email,
            'tel'          => $this->tel,
            'view_count'   => $this->viewCount,
            'evaluation'   => $this->evaluation,
            'review_counts' => $this->reviewCounts,
            'allow_status' => $this->allowStatus,
            'status'       => $this->status,
            'created_at'   => $this->createdAt,
            'updated_at'   => $this->updatedAt,
            'account'      => $this->account,
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

    private static function account(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('accountHospital') || ! $hospital->accountHospital) {
            return null;
        }

        $account = $hospital->accountHospital;

        return [
            'id' => (int) $account->getKey(),
            'nickname' => (string) $account->nickname,
            'email' => (string) $account->email,
            'status' => (string) $account->status,
            'last_login_at' => $account->last_login_at?->toISOString(),
        ];
    }

    private static function logo(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('logoMedia')) {
            return null;
        }

        return self::media($hospital->logoMedia);
    }

    private static function categories(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('categories')) {
            return null;
        }

        return $hospital->categories
            ->map(fn (Category $category): array => [
                'name' => (string) $category->name,
            ])
            ->values()
            ->all();
    }

    private static function features(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('features')) {
            return null;
        }

        return $hospital->features
            ->map(fn (HospitalFeature $feature): array => [
                'code' => (string) $feature->code,
                'name' => (string) $feature->name,
            ])
            ->values()
            ->all();
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
