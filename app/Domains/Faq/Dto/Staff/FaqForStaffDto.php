<?php

namespace App\Domains\Faq\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Faq\Models\Faq;
use Illuminate\Support\Collection;

final readonly class FaqForStaffDto
{
    public function __construct(public array $faq) {}

    public static function fromModel(Faq $faq): self
    {
        $primaryCategory = self::resolvePrimaryCategory($faq);

        return new self([
            'id' => (int) $faq->id,
            'category_id' => $primaryCategory ? (int) $primaryCategory->id : null,
            'category' => $primaryCategory
                ? [
                    'id' => (int) $primaryCategory->id,
                    'name' => (string) $primaryCategory->name,
                    'domain' => (string) $primaryCategory->domain,
                    'status' => (string) $primaryCategory->status,
                    'sort_order' => (int) $primaryCategory->sort_order,
                    'is_primary' => (bool) ($primaryCategory->pivot?->is_primary ?? false),
                ]
                : null,
            'channel' => (string) $faq->channel,
            'question' => (string) $faq->question,
            'status' => (string) $faq->status,
            'sort_order' => (int) $faq->sort_order,
            'view_count' => (int) $faq->view_count,
            'created_by_staff_id' => $faq->created_by_staff_id ? (int) $faq->created_by_staff_id : null,
            'updated_by_staff_id' => $faq->updated_by_staff_id ? (int) $faq->updated_by_staff_id : null,
            'created_at' => $faq->created_at?->toISOString(),
            'updated_at' => $faq->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->faq;
    }

    /**
     * @return Collection<int, Category>
     */
    private static function resolveCategories(Faq $faq): Collection
    {
        if (! $faq->relationLoaded('categories')) {
            return collect();
        }

        return $faq->categories;
    }

    private static function resolvePrimaryCategory(Faq $faq): ?Category
    {
        $categories = self::resolveCategories($faq);

        return $categories->first(static fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ?? $categories->first();
    }
}
