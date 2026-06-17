<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvent\Models\HospitalEvent;

final readonly class HospitalEventForStaffDto
{
    public function __construct(
        public int $id,
        public ?array $hospital,
        public string $eventType,
        public bool $isMaleTargeted,
        public string $name,
        public string $description,
        public bool $isEventPeriodUnlimited,
        public ?string $eventStartAt,
        public ?string $eventEndAt,
        public int $normalPrice,
        public int $eventPrice,
        public bool $isVatIncluded,
        public int $discountRate,
        public int $baseConsultationPrice,
        public int $consultationPrice,
        public bool $hasOptions,
        public string $allowStatus,
        public string $status,
        public int $viewCount,
        public int $consultationCount,
        public int $confirmedConsultationCount,
        public int $totalSpentPoint,
        public array $categories,
        public array $doctors,
        public ?array $thumbnailImage,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(HospitalEvent $event): self
    {
        return new self(
            id: (int) $event->id,
            hospital: self::hospital($event),
            eventType: (string) $event->event_type,
            isMaleTargeted: (bool) $event->is_male_targeted,
            name: (string) $event->name,
            description: (string) $event->description,
            isEventPeriodUnlimited: (bool) $event->is_event_period_unlimited,
            eventStartAt: $event->event_start_at?->toDateString(),
            eventEndAt: $event->event_end_at?->toDateString(),
            normalPrice: (int) $event->normal_price,
            eventPrice: (int) $event->event_price,
            isVatIncluded: (bool) $event->is_vat_included,
            discountRate: (int) $event->discount_rate,
            baseConsultationPrice: (int) $event->base_consultation_price,
            consultationPrice: (int) $event->consultation_price,
            hasOptions: (bool) $event->has_options,
            allowStatus: (string) $event->allow_status,
            status: (string) $event->status,
            viewCount: (int) $event->view_count,
            consultationCount: (int) ($event->consultation_count ?? 0),
            confirmedConsultationCount: (int) ($event->confirmed_consultation_count ?? 0),
            totalSpentPoint: (int) ($event->total_spent_point ?? 0),
            categories: self::categories($event),
            doctors: self::doctors($event),
            thumbnailImage: self::thumbnailImage($event),
            createdAt: $event->created_at?->toISOString() ?? '',
            updatedAt: $event->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hospital' => $this->hospital,
            'event_type' => $this->eventType,
            'is_male_targeted' => $this->isMaleTargeted,
            'name' => $this->name,
            'description' => $this->description,
            'is_event_period_unlimited' => $this->isEventPeriodUnlimited,
            'event_start_at' => $this->eventStartAt,
            'event_end_at' => $this->eventEndAt,
            'normal_price' => $this->normalPrice,
            'event_price' => $this->eventPrice,
            'is_vat_included' => $this->isVatIncluded,
            'discount_rate' => $this->discountRate,
            'base_consultation_price' => $this->baseConsultationPrice,
            'consultation_price' => $this->consultationPrice,
            'has_options' => $this->hasOptions,
            'allow_status' => $this->allowStatus,
            'status' => $this->status,
            'view_count' => $this->viewCount,
            'consultation_count' => $this->consultationCount,
            'confirmed_consultation_count' => $this->confirmedConsultationCount,
            'total_spent_point' => $this->totalSpentPoint,
            'categories' => $this->categories,
            'doctors' => $this->doctors,
            'thumbnail_image' => $this->thumbnailImage,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    private static function hospital(HospitalEvent $event): ?array
    {
        if (! $event->relationLoaded('hospital') || ! $event->hospital) {
            return null;
        }

        return [
            'id' => (int) $event->hospital->id,
            'name' => (string) $event->hospital->name,
            'manager' => self::hospitalManager($event),
        ];
    }

    private static function hospitalManager(HospitalEvent $event): ?array
    {
        if (! $event->hospital->relationLoaded('accountHospital') || ! $event->hospital->accountHospital) {
            return null;
        }

        $account = $event->hospital->accountHospital;

        return [
            'id' => (int) $account->id,
            'name' => (string) ($account->name ?: $account->nickname ?: $account->email),
            'nickname' => $account->nickname,
            'email' => $account->email,
        ];
    }

    private static function categories(HospitalEvent $event): array
    {
        if (! $event->relationLoaded('categories')) {
            return [];
        }

        return $event->categories
            ->filter(static fn (Category $category): bool => (int) ($category->depth ?? 0) === 3)
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'code' => (string) ($category->code ?? ''),
                'domain' => (string) ($category->domain ?? ''),
                'name' => (string) $category->name,
                'full_path' => (string) ($category->full_path ?? ''),
                'depth' => (int) ($category->depth ?? 0),
                'usage' => self::categoryUsage($category),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->values()
            ->all();
    }

    private static function doctors(HospitalEvent $event): array
    {
        if (! $event->relationLoaded('doctors')) {
            return [];
        }

        return $event->doctors
            ->map(fn (HospitalDoctor $doctor): array => [
                'id' => (int) $doctor->id,
                'name' => (string) $doctor->name,
                'position' => $doctor->position,
                'is_career_visible' => (bool) ($doctor->pivot?->is_career_visible ?? true),
                'is_activity_visible' => (bool) ($doctor->pivot?->is_activity_visible ?? false),
            ])
            ->values()
            ->all();
    }

    private static function thumbnailImage(HospitalEvent $event): ?array
    {
        if (! $event->relationLoaded('thumbnailImage')) {
            return null;
        }

        return self::media($event->thumbnailImage);
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
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'sort_order' => (int) $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }

    private static function categoryUsage(Category $category): ?string
    {
        static $pathsByUsage = null;

        $pathsByUsage ??= CategoryUsage::activeCategoryFullPathsByUsage([
            CategoryUsage::USAGE_HOSPITAL_EVENT_SURGERY,
            CategoryUsage::USAGE_HOSPITAL_EVENT_TREATMENT,
        ]);

        $fullPath = trim((string) ($category->full_path ?? ''));
        if ($fullPath === '') {
            $fullPath = trim((string) $category->name);
        }

        foreach ($pathsByUsage as $usage => $rootPaths) {
            foreach ($rootPaths as $rootPath) {
                $rootPath = trim((string) $rootPath);
                if ($rootPath === '') {
                    continue;
                }

                if ($fullPath === $rootPath || str_starts_with($fullPath, $rootPath.' > ')) {
                    return (string) $usage;
                }
            }
        }

        return null;
    }
}
