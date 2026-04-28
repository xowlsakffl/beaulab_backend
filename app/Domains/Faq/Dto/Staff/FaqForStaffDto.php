<?php

namespace App\Domains\Faq\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Faq\Models\Faq;
use Illuminate\Support\Collection;

/**
 * FaqForStaffDto DTO.
 */
final readonly class FaqForStaffDto
{
    public function __construct(
        public int $id,
        public ?int $categoryId,
        public ?array $category,
        public string $channel,
        public string $question,
        public string $status,
        public int $sortOrder,
        public int $viewCount,
        public ?int $createdByStaffId,
        public ?int $updatedByStaffId,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(Faq $faq): self
    {
        $primaryCategory = self::resolvePrimaryCategory($faq);

        return new self(
            id: (int) $faq->id,
            categoryId: $primaryCategory ? (int) $primaryCategory->id : null,
            category: self::category($primaryCategory),
            channel: (string) $faq->channel,
            question: (string) $faq->question,
            status: (string) $faq->status,
            sortOrder: (int) $faq->sort_order,
            viewCount: (int) $faq->view_count,
            createdByStaffId: $faq->created_by_staff_id ? (int) $faq->created_by_staff_id : null,
            updatedByStaffId: $faq->updated_by_staff_id ? (int) $faq->updated_by_staff_id : null,
            createdAt: $faq->created_at?->toISOString(),
            updatedAt: $faq->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'category_id' => $this->categoryId,
            'category' => $this->category,
            'channel' => $this->channel,
            'question' => $this->question,
            'status' => $this->status,
            'sort_order' => $this->sortOrder,
            'view_count' => $this->viewCount,
            'created_by_staff_id' => $this->createdByStaffId,
            'updated_by_staff_id' => $this->updatedByStaffId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    private static function category(?Category $category): ?array
    {
        if (! $category) {
            return null;
        }

        return [
            'id' => (int) $category->id,
            'name' => (string) $category->name,
            'domain' => (string) $category->domain,
            'status' => (string) $category->status,
            'sort_order' => (int) $category->sort_order,
            'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
        ];
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
