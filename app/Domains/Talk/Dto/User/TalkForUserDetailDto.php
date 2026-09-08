<?php

namespace App\Domains\Talk\Dto\User;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkPollOption;

final readonly class TalkForUserDetailDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $content,
        public string $status,
        public int $viewCount,
        public int $commentCount,
        public int $likeCount,
        public int $saveCount,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $author = null,
        public ?array $category = null,
        public ?array $images = null,
        public ?array $poll = null,
    ) {}

    public static function fromModel(Talk $talk): self
    {
        return new self(
            id: (int) $talk->id,
            title: (string) $talk->title,
            content: (string) $talk->content,
            status: (string) $talk->status,
            viewCount: (int) $talk->view_count,
            commentCount: (int) $talk->comment_count,
            likeCount: (int) $talk->like_count,
            saveCount: (int) $talk->save_count,
            createdAt: $talk->created_at?->toISOString(),
            updatedAt: $talk->updated_at?->toISOString(),
            author: self::author($talk),
            category: self::category($talk),
            images: self::images($talk),
            poll: self::poll($talk),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
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

        if ($this->category !== null) {
            $data['category'] = $this->category;
        }

        if ($this->images !== null) {
            $data['images'] = $this->images;
        }

        if ($this->poll !== null) {
            $data['poll'] = $this->poll;
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

    private static function category(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('categories')) {
            return null;
        }

        $category = $talk->categories
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->first();

        if (! $category instanceof Category) {
            return null;
        }

        $attributes = $category->getAttributes();

        return [
            'id' => (int) $category->id,
            'code' => (string) ($attributes['code'] ?? ''),
            'domain' => (string) ($attributes['domain'] ?? Talk::CATEGORY_DOMAIN),
            'name' => (string) $category->name,
            'full_path' => (string) ($attributes['full_path'] ?? ''),
            'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
        ];
    }

    private static function images(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('images')) {
            return null;
        }

        return $talk->images
            ->map(fn (Media $media): array => [
                'id' => (int) $media->id,
                'collection' => (string) $media->collection,
                'disk' => (string) $media->disk,
                'path' => $media->publicPath(),
                'mime_type' => (string) $media->mime_type,
                'size' => (int) $media->size,
                'width' => $media->width !== null ? (int) $media->width : null,
                'height' => $media->height !== null ? (int) $media->height : null,
                'sort_order' => (int) $media->sort_order,
                'is_primary' => (bool) $media->is_primary,
                'metadata' => $media->publicMetadata(),
                'created_at' => $media->created_at?->toISOString(),
                'updated_at' => $media->updated_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private static function poll(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('poll') || ! $talk->poll) {
            return null;
        }

        return [
            'id' => (int) $talk->poll->id,
            'allow_multiple' => (bool) $talk->poll->allow_multiple,
            'options' => $talk->poll->relationLoaded('options')
                ? $talk->poll->options
                    ->map(fn (TalkPollOption $option): array => [
                        'id' => (int) $option->id,
                        'content' => (string) $option->content,
                        'sort_order' => (int) $option->sort_order,
                        'vote_count' => (int) $option->vote_count,
                    ])
                    ->values()
                    ->all()
                : [],
            'created_at' => $talk->poll->created_at?->toISOString(),
            'updated_at' => $talk->poll->updated_at?->toISOString(),
        ];
    }
}
