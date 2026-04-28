<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Models\TalkPollOption;
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
        public ?int $categoryId,
        public array $images,
        public ?array $poll,
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
            categoryId: self::categoryId($talk),
            images: self::images($talk),
            poll: self::poll($talk),
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
            'category_id' => $this->categoryId,
            'images' => $this->images,
            'poll' => $this->poll,
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

    private static function categoryId(Talk $talk): ?int
    {
        $id = self::resolveCategories($talk)
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->map(fn (Category $category): int => (int) $category->id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->first();

        return is_int($id) && $id > 0 ? $id : null;
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
            ->map(fn (OperationHistory $history): array => self::commentOperationHistory($history))
            ->values()
            ->all();
    }

    private static function commentOperationHistory(OperationHistory $history): array
    {
        $field = (string) $history->field;

        return [
            'status' => $field === 'status' ? $history->after_value : null,
            'post_status' => $field === 'post_status' ? $history->after_value : null,
            'created_at' => $history->created_at?->toISOString(),
            'reason' => $history->reason,
        ];
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
