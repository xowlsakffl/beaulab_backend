<?php

namespace App\Domains\Talk\Dto\User;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Talk\Models\Talk;
use Illuminate\Support\Collection;

final readonly class TalkCreateForUserDto
{
    public function __construct(public array $talk) {}

    public static function fromModel(Talk $talk): self
    {
        return new self([
            'id' => (int) $talk->id,
            'author_id' => $talk->author_id ? (int) $talk->author_id : null,
            'author' => $talk->relationLoaded('author') && $talk->author
                ? [
                    'id' => (int) $talk->author->id,
                    'name' => (string) $talk->author->name,
                    'nickname' => $talk->author->nickname ? (string) $talk->author->nickname : null,
                ]
                : null,
            'title' => (string) $talk->title,
            'content' => (string) $talk->content,
            'status' => (string) $talk->status,
            'post_status' => (string) $talk->post_status,
            'view_count' => (int) $talk->view_count,
            'comment_count' => (int) $talk->comment_count,
            'like_count' => (int) $talk->like_count,
            'save_count' => (int) $talk->save_count,
            'categories' => self::resolveCategories($talk)
                ->map(fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'name' => (string) $category->name,
                    'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                ])
                ->values()
                ->all(),
            'images' => self::resolveImages($talk)
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
                ->all(),
            'created_at' => $talk->created_at?->toISOString(),
            'updated_at' => $talk->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->talk;
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
