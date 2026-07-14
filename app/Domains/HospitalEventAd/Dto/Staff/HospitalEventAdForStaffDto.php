<?php

namespace App\Domains\HospitalEventAd\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;

final readonly class HospitalEventAdForStaffDto
{
    public function __construct(private HospitalEventAd $ad) {}

    public static function fromModel(HospitalEventAd $ad): self
    {
        return new self($ad);
    }

    public function toArray(): array
    {
        $adStatus = $this->ad->adStatus();

        return [
            'id' => (int) $this->ad->id,
            'hospital' => $this->hospital(),
            'hospital_event' => $this->hospitalEvent(),
            'category' => $this->category(),
            'categories' => $this->categories(),
            'manager_staff' => $this->managerStaff(),
            'placement' => (string) $this->ad->placement,
            'placement_label' => HospitalEventAd::placementLabel((string) $this->ad->placement),
            'placement_group_label' => HospitalEventAd::placementGroupLabel((string) $this->ad->placement),
            'cost' => (int) $this->ad->cost,
            'start_at' => $this->ad->start_at?->toISOString(),
            'end_at' => $this->ad->end_at?->toISOString(),
            'allow_status' => (string) $this->ad->allow_status,
            'allow_status_label' => HospitalEventAd::allowStatusLabel((string) $this->ad->allow_status),
            'ad_status' => $adStatus,
            'ad_status_label' => HospitalEventAd::adStatusLabel($adStatus),
            'created_at' => $this->ad->created_at?->toISOString(),
            'updated_at' => $this->ad->updated_at?->toISOString(),
        ];
    }

    private function hospital(): ?array
    {
        if (! $this->ad->relationLoaded('hospital') || ! $this->ad->hospital) {
            return null;
        }

        return [
            'id' => (int) $this->ad->hospital->id,
            'name' => (string) $this->ad->hospital->name,
        ];
    }

    private function hospitalEvent(): ?array
    {
        if (! $this->ad->relationLoaded('hospitalEvent') || ! $this->ad->hospitalEvent instanceof HospitalEvent) {
            return null;
        }

        return [
            'id' => (int) $this->ad->hospitalEvent->id,
            'name' => (string) $this->ad->hospitalEvent->name,
            'allow_status' => (string) $this->ad->hospitalEvent->allow_status,
            'hospital_status' => (string) $this->ad->hospitalEvent->hospital_status,
            'admin_status' => (string) $this->ad->hospitalEvent->admin_status,
        ];
    }

    private function category(): ?array
    {
        $category = $this->primaryCategory();
        if (! $category instanceof Category) {
            return null;
        }

        return [
            'id' => (int) $category->id,
            'code' => (string) ($category->code ?? ''),
            'name' => (string) $category->name,
            'full_path' => (string) ($category->full_path ?? ''),
            'depth' => (int) ($category->depth ?? 0),
        ];
    }

    private function categories(): array
    {
        if (! $this->ad->relationLoaded('categories')) {
            return [];
        }

        return $this->ad->categories
            ->map(static fn (Category $category): array => [
                'id' => (int) $category->id,
                'code' => (string) ($category->code ?? ''),
                'name' => (string) $category->name,
                'full_path' => (string) ($category->full_path ?? ''),
                'depth' => (int) ($category->depth ?? 0),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->values()
            ->all();
    }

    private function primaryCategory(): ?Category
    {
        if (! $this->ad->relationLoaded('categories')) {
            return null;
        }

        return $this->ad->categories
            ->sortByDesc(static fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->first();
    }

    private function managerStaff(): ?array
    {
        if (! $this->ad->relationLoaded('managerStaff') || ! $this->ad->managerStaff) {
            return null;
        }

        return [
            'id' => (int) $this->ad->managerStaff->id,
            'name' => (string) $this->ad->managerStaff->name,
            'email' => $this->ad->managerStaff->email,
        ];
    }
}
