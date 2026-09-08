<?php

namespace App\Domains\Faq\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Faq\Models\Faq;

/**
 * FaqForStaffDetailDto DTO.
 */
final readonly class FaqForStaffDetailDto
{
    public function __construct(
        public int $id,
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
        public string $content,
        public ?array $creator,
        public ?array $updater,
    ) {}

    public static function fromModel(Faq $faq): self
    {
        return new self(
            id: (int) $faq->id,
            category: self::category($faq),
            channel: (string) $faq->channel,
            question: (string) $faq->question,
            status: (string) $faq->status,
            sortOrder: (int) $faq->sort_order,
            viewCount: (int) $faq->view_count,
            createdByStaffId: $faq->created_by_staff_id ? (int) $faq->created_by_staff_id : null,
            updatedByStaffId: $faq->updated_by_staff_id ? (int) $faq->updated_by_staff_id : null,
            createdAt: $faq->created_at?->toISOString(),
            updatedAt: $faq->updated_at?->toISOString(),
            content: \App\Domains\Common\Media\Support\EditorHtmlSanitizer::clean((string) $faq->content),
            creator: self::creator($faq),
            updater: self::updater($faq),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
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
            'content' => $this->content,
            'creator' => $this->creator,
            'updater' => $this->updater,
        ];

        return $data;
    }

    private static function category(Faq $faq): ?array
    {
        if (! $faq->relationLoaded('categories')) {
            return null;
        }

        $category = $faq->categories->first(static fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ?? $faq->categories->first();

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

    private static function creator(Faq $faq): ?array
    {
        if (! $faq->relationLoaded('creator') || ! $faq->creator) {
            return null;
        }

        return [
            'id' => (int) $faq->creator->id,
            'name' => (string) $faq->creator->name,
            'email' => (string) $faq->creator->email,
        ];
    }

    private static function updater(Faq $faq): ?array
    {
        if (! $faq->relationLoaded('updater') || ! $faq->updater) {
            return null;
        }

        return [
            'id' => (int) $faq->updater->id,
            'name' => (string) $faq->updater->name,
            'email' => (string) $faq->updater->email,
        ];
    }
}
