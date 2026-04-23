<?php

namespace App\Domains\Talk\Dto\User;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Talk\Models\Talk;
use Illuminate\Support\Collection;

final readonly class TalkCreateForUserDto
{
    public function __construct(
        public int $id,
        public ?int $authorId,
        public string $title,
        public string $content,
        public string $status,
        public string $postStatus,
        public int $viewCount,
        public int $commentCount,
        public int $likeCount,
        public int $saveCount,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $author = null,
        public ?string $categoryCode = null,
        public ?array $images = null,
    ) {}

    public static function fromModel(Talk $talk): self
    {
        return new self(
            id: (int) $talk->id,
            authorId: $talk->author_id ? (int) $talk->author_id : null,
            title: (string) $talk->title,
            content: (string) $talk->content,
            status: (string) $talk->status,
            postStatus: (string) $talk->post_status,
            viewCount: (int) $talk->view_count,
            commentCount: (int) $talk->comment_count,
            likeCount: (int) $talk->like_count,
            saveCount: (int) $talk->save_count,
            createdAt: $talk->created_at?->toISOString(),
            updatedAt: $talk->updated_at?->toISOString(),
            author: $talk->relationLoaded('author') ? self::author($talk) : null,
            categoryCode: $talk->relationLoaded('categories') ? self::categoryCode($talk) : null,
            images: $talk->relationLoaded('images') ? self::images($talk) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'author_id' => $this->authorId,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'post_status' => $this->postStatus,
            'view_count' => $this->viewCount,
            'comment_count' => $this->commentCount,
            'like_count' => $this->likeCount,
            'save_count' => $this->saveCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->author !== null) {
            $data['author'] = $this->author;
        }

        if ($this->categoryCode !== null) {
            $data['category_code'] = $this->categoryCode;
        }

        if ($this->images !== null) {
            $data['images'] = $this->images;
        }

        return $data;
    }

    private static function author(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('author') || ! $talk->author) {
            return null;
        }

        return [
            'id' => (int) $talk->author->id,
            'name' => (string) $talk->author->name,
            'nickname' => $talk->author->nickname ? (string) $talk->author->nickname : null,
        ];
    }

    private static function categoryCode(Talk $talk): ?string
    {
        $code = self::resolveCategories($talk)
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->map(fn (Category $category): string => (string) $category->code)
            ->filter(static fn (string $code): bool => $code !== '')
            ->first();

        return is_string($code) && $code !== '' ? $code : null;
    }

    private static function images(Talk $talk): array
    {
        return self::resolveImages($talk)
            ->map(fn (Media $media): array => [
                'id' => (int) $media->id,
                'collection' => (string) $media->collection,
                'disk' => (string) $media->disk,
                'path' => (string) $media->path,
                'mime_type' => (string) $media->mime_type,
                'size' => (int) $media->size,
                'width' => $media->width !== null ? (int) $media->width : null,
                'height' => $media->height !== null ? (int) $media->height : null,
                'sort_order' => (int) $media->sort_order,
                'created_at' => $media->created_at?->toISOString(),
                'updated_at' => $media->updated_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Category>
     */
    private static function resolveCategories(Talk $talk): Collection
    {
        if (! $talk->relationLoaded('categories')) {
            return collect();
        }

        return $talk->categories;
    }

    /**
     * @return Collection<int, Media>
     */
    private static function resolveImages(Talk $talk): Collection
    {
        if (! $talk->relationLoaded('images')) {
            return collect();
        }

        return $talk->images;
    }
}
