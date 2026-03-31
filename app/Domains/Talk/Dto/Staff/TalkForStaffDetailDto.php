<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\Dto\AdminNote\AdminNoteData;
use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Support\Collection;

final readonly class TalkForStaffDetailDto
{
    public function __construct(public array $talk) {}

    public static function fromModel(Talk $talk, bool $includeComments = false): self
    {
        $data = [
            'id' => (int) $talk->id,
            'author_id' => $talk->author_id ? (int) $talk->author_id : null,
            'author' => $talk->relationLoaded('author') && $talk->author
                ? [
                    'id' => (int) $talk->author->id,
                    'name' => (string) $talk->author->name,
                    'email' => (string) $talk->author->email,
                ]
                : null,
            'title' => (string) $talk->title,
            'content' => (string) $talk->content,
            'status' => (string) $talk->status,
            'is_visible' => (bool) $talk->is_visible,
            'author_ip' => $talk->author_ip,
            'is_pinned' => (bool) $talk->is_pinned,
            'pinned_order' => (int) $talk->pinned_order,
            'view_count' => (int) $talk->view_count,
            'comment_count' => (int) $talk->comment_count,
            'like_count' => (int) $talk->like_count,
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
            'admin_notes' => self::resolveAdminNotes($talk)
                ->map(fn (AdminNote $note): array => AdminNoteData::fromModel($note)->toArray())
                ->values()
                ->all(),
            'created_at' => $talk->created_at?->toISOString(),
            'updated_at' => $talk->updated_at?->toISOString(),
            'deleted_at' => $talk->deleted_at?->toISOString(),
        ];

        if ($includeComments) {
            $data['comments'] = self::resolveComments($talk)
                ->map(fn (TalkComment $comment): array => [
                    'id' => (int) $comment->id,
                    'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
                    'is_reply' => $comment->isReply(),
                    'author_id' => $comment->author_id ? (int) $comment->author_id : null,
                    'author_name' => $comment->relationLoaded('author') && $comment->author
                        ? (string) $comment->author->name
                        : null,
                    'content' => (string) $comment->content,
                    'status' => (string) $comment->status,
                    'is_visible' => (bool) $comment->is_visible,
                    'author_ip' => $comment->author_ip,
                    'like_count' => (int) $comment->like_count,
                    'created_at' => $comment->created_at?->toISOString(),
                    'updated_at' => $comment->updated_at?->toISOString(),
                    'deleted_at' => $comment->deleted_at?->toISOString(),
                ])
                ->values()
                ->all();
        }

        return new self($data);
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

    /**
     * @return Collection<int, AdminNote>
     */
    private static function resolveAdminNotes(Talk $talk): Collection
    {
        if (! $talk->relationLoaded('adminNotes')) {
            return collect();
        }

        return $talk->adminNotes;
    }

    /**
     * @return Collection<int, TalkComment>
     */
    private static function resolveComments(Talk $talk): Collection
    {
        if (! $talk->relationLoaded('comments')) {
            return collect();
        }

        return $talk->comments;
    }
}
