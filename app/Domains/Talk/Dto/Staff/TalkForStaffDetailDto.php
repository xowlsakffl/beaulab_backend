<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\Dto\OperationHistory\OperationHistoryDto;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Common\Models\OperationHistory\OperationHistory;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Support\Collection;

/**
 * TalkForStaffDetailDto 역할 정의.
 * 토크 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class TalkForStaffDetailDto
{
    public function __construct(
        public int $id,
        public ?int $authorId,
        public string $title,
        public string $content,
        public string $status,
        public string $postStatus,
        public ?string $authorIp,
        public bool $isPinned,
        public int $pinnedOrder,
        public int $viewCount,
        public int $commentCount,
        public int $likeCount,
        public int $saveCount,
        public ?array $author,
        public ?string $categoryCode,
        public array $images,
        public array $operationHistories,
        public array $comments,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(
        Talk $talk,
        array $operationHistories,
        array $comments,
    ): self
    {
        return new self(
            id: (int) $talk->id,
            authorId: $talk->author_id ? (int) $talk->author_id : null,
            title: (string) $talk->title,
            content: (string) $talk->content,
            status: (string) $talk->status,
            postStatus: (string) $talk->post_status,
            authorIp: $talk->author_ip,
            isPinned: (bool) $talk->is_pinned,
            pinnedOrder: (int) $talk->pinned_order,
            viewCount: (int) $talk->view_count,
            commentCount: (int) $talk->comment_count,
            likeCount: (int) $talk->like_count,
            saveCount: (int) $talk->save_count,
            author: self::author($talk),
            categoryCode: self::categoryCode($talk),
            images: self::images($talk),
            operationHistories: $operationHistories,
            comments: $comments,
            createdAt: $talk->created_at?->toISOString(),
            updatedAt: $talk->updated_at?->toISOString(),
            deletedAt: $talk->deleted_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'author_id' => $this->authorId,
            'author' => $this->author,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'post_status' => $this->postStatus,
            'author_ip' => $this->authorIp,
            'is_pinned' => $this->isPinned,
            'pinned_order' => $this->pinnedOrder,
            'view_count' => $this->viewCount,
            'comment_count' => $this->commentCount,
            'like_count' => $this->likeCount,
            'save_count' => $this->saveCount,
            'category_code' => $this->categoryCode,
            'images' => $this->images,
            'operation_histories' => $this->operationHistories,
            'comments' => $this->comments,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];

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
            'email' => (string) $talk->author->email,
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

    public static function operationHistory(OperationHistory $history): array
    {
        return OperationHistoryDto::fromModel($history)->toArray();
    }

    public static function comment(TalkComment $comment): array
    {
        return [
            'id' => (int) $comment->id,
            'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
            'is_reply' => $comment->isReply(),
            'author_id' => $comment->author_id ? (int) $comment->author_id : null,
            'author_name' => $comment->relationLoaded('author') && $comment->author
                ? (string) $comment->author->name
                : null,
            'content' => (string) $comment->content,
            'status' => (string) $comment->status,
            'post_status' => (string) $comment->post_status,
            'author_ip' => $comment->author_ip,
            'like_count' => (int) $comment->like_count,
            'operation_histories' => self::commentOperationHistories($comment),
            'created_at' => $comment->created_at?->toISOString(),
            'updated_at' => $comment->updated_at?->toISOString(),
            'deleted_at' => $comment->deleted_at?->toISOString(),
        ];
    }

    private static function commentOperationHistories(TalkComment $comment): array
    {
        if (! $comment->relationLoaded('operationHistories')) {
            return [];
        }

        return $comment->operationHistories
            ->map(fn (OperationHistory $history): array => self::operationHistory($history))
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
