<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventOption;

final readonly class HospitalEventForStaffDetailDto
{
    public static function fromModel(HospitalEvent $event): self
    {
        return new self($event);
    }

    private function __construct(private HospitalEvent $event) {}

    public function toArray(): array
    {
        return [
            'id' => (int) $this->event->id,
            'hospital' => $this->hospital(),
            'event_type' => (string) $this->event->event_type,
            'is_male_targeted' => (bool) $this->event->is_male_targeted,
            'name' => (string) $this->event->name,
            'description' => (string) $this->event->description,
            'is_event_period_unlimited' => (bool) $this->event->is_event_period_unlimited,
            'event_start_at' => $this->event->event_start_at?->toDateString(),
            'event_end_at' => $this->event->event_end_at?->toDateString(),
            'normal_price' => (int) $this->event->normal_price,
            'event_price' => (int) $this->event->event_price,
            'is_vat_included' => (bool) $this->event->is_vat_included,
            'discount_rate' => (int) $this->event->discount_rate,
            'base_consultation_price' => (int) $this->event->base_consultation_price,
            'consultation_price' => (int) $this->event->consultation_price,
            'has_options' => (bool) $this->event->has_options,
            'procedure_targets' => $this->event->procedure_targets ?? [],
            'procedure_benefits' => $this->event->procedure_benefits ?? [],
            'side_effect_notice' => $this->event->side_effect_notice,
            'allow_status' => (string) $this->event->allow_status,
            'hospital_status' => (string) $this->event->hospital_status,
            'admin_status' => (string) $this->event->admin_status,
            'view_count' => (int) $this->event->view_count,
            'consultation_count' => (int) $this->event->consultation_count,
            'categories' => $this->categories(),
            'doctors' => $this->doctors(),
            'options' => $this->options(),
            'thumbnail_image' => $this->thumbnailImage(),
            'event_page_image' => $this->eventPageImage(),
            'created_at' => $this->event->created_at?->toISOString(),
            'updated_at' => $this->event->updated_at?->toISOString(),
            'deleted_at' => $this->event->deleted_at?->toISOString(),
        ];
    }

    private function hospital(): ?array
    {
        if (! $this->event->relationLoaded('hospital') || ! $this->event->hospital) {
            return null;
        }

        $businessNumber = null;
        if ($this->event->hospital->relationLoaded('businessRegistration') && $this->event->hospital->businessRegistration) {
            $businessNumber = $this->event->hospital->businessRegistration->business_number;
        }

        return [
            'id' => (int) $this->event->hospital->id,
            'name' => (string) $this->event->hospital->name,
            'business_number' => $businessNumber,
        ];
    }

    private function categories(): array
    {
        if (! $this->event->relationLoaded('categories')) {
            return [];
        }

        return $this->event->categories
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

    private function doctors(): array
    {
        if (! $this->event->relationLoaded('doctors')) {
            return [];
        }

        return $this->event->doctors
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

    private function options(): array
    {
        if (! $this->event->relationLoaded('options')) {
            return [];
        }

        return $this->event->options
            ->map(fn (HospitalEventOption $option): array => [
                'id' => (int) $option->id,
                'sort_order' => (int) $option->sort_order,
                'name' => (string) $option->name,
                'session_count' => (int) $option->session_count,
                'normal_price' => (int) $option->normal_price,
                'event_price' => (int) $option->event_price,
                'discount_rate' => (int) $option->discount_rate,
            ])
            ->values()
            ->all();
    }

    private function thumbnailImage(): ?array
    {
        if (! $this->event->relationLoaded('thumbnailImage')) {
            return null;
        }

        return $this->media($this->event->thumbnailImage);
    }

    private function eventPageImage(): ?array
    {
        if (! $this->event->relationLoaded('eventPageImage')) {
            return null;
        }

        return $this->media($this->event->eventPageImage);
    }

    private function media(?Media $media): ?array
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
